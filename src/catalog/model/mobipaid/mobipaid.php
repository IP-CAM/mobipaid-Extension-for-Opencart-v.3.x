<?php
/* Mobipaid payment method model
 *
 * @version 1.0.0
 * @date 2020-05-27
 *
 */
include_once(dirname(__FILE__) . '/../../controller/mobipaid/mobipaid.php');
include_once(dirname(__FILE__) . '/../../controller/mobipaid/mobipaidapi.php');

class ModelMobipaidMobipaid extends Model
{
	/**
	 * this variable is Code
	 *
	 * @var string $code
	 */
	protected $code = '';

	/**
	 * this variable is title
	 *
	 * @var string $title
	 */
	protected $title = '';

	/**
	 * this variable is logo
	 *
	 * @var string $logo
	 */
	protected $logo = '';

	/**
	 * Get the Method
	 * this funtion is the OpenCart funtion
	 *
	 * @param string $address
	 * @param int $total
	 * @return  array
	 */
	public function getMethod($address, $total) {
		$this->language->load('extension/payment/mobipaid');
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_' . $this->code . '_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_' . $this->code . '_total') > 0 && $this->config->get('payment_' . $this->code . '_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('payment_' . $this->code . '_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}
		$method_data = array();
		if ($status) {
			$method_data = array(
				'code'       	=> $this->code,
				'title'      	=> $this->language->get($this->title),
				'logo'			=> $this->getLogo(),
				'terms'		 	=> '',
				'sort_order' 	=> ''
			);
		}
		return $method_data;
	}

	public function log($log_message)
	{
		$file_name = DIR_LOGS . 'mobipaid-' . date('d-m-Y') . '.log';
		$time = date('d-m-Y h:i:sa - ');
		file_put_contents($file_name, $time . $log_message . "\n", FILE_APPEND);
	}

	/**
	 * get the payment method logo
	 *
	 * @return string
	 */
	public function getLogo() {

		if (file_exists('catalog/view/theme/' . $this->config->get('config_template') . '/image/mobipaid/' . $this->logo)) {
			$logo_html = '<img src="catalog/view/theme/' . $this->config->get('config_template') . '/image/mobipaid/' . $this->logo . '" border="0" style="height:35px;">';
		} else {
			$logo_html = '<img src="catalog/view/theme/default/image/mobipaid/' . $this->logo . '" border="0" style="height:35px;">';
		}
		return $logo_html;
	}

	/**
	 * Get the product price
	 *
	 * @param   string  $product_id
	 * @return  boolean|array
	 */
	function getProductPrice($product_id)
	{
		$query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		if ($query->num_rows) {
			return $query->row['price'];
		}
		return false;
	}


	/**
	 * this function is getCartAmount
	 *
	 * @return array $totals
	 */
	public function getCartAmount(){
		$this->load->model('setting/extension');

		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Because __call can not keep var references so we put them into an array.
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);

		// Display prices
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);
		}

		return $totals;
	}

	/**
	 * Do refund Payment
	 *
	 * @param   array  $order_info
	 * @param   string  $order_status_id
	 * @return  array
	 */
	public function refundPayment($order_info, $order_status_id) {
		$order_id = $order_info['order_id'];
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "payment_mobipaid_orders WHERE order_id = '" . (int)$order_id . "'");
		$mobipaid_order = $query->row;
		$this->log('refundPayment - mobipaid order : ' . print_r($mobipaid_order, true));
		$this->log('refundPayment - order Info : ' . print_r($order_info, true));

		if (empty($mobipaid_order)) {
			return array('status' => false, 'errorMessage' => 'ERROR_MOBIPAID_REFUND_PAYMENT');
		}

		$status_refund_id = $this->config->get('payment_mobipaid_refund_status_id');
		$this->log('refundPayment - status refund id : ' . print_r($status_refund_id, true));

		$payment_id = $mobipaid_order['payment_id'];

		$payment_result['order_status_id'] = $order_status_id;

		if ($order_status_id == $status_refund_id) {
			$body = array(
				'email'		=> $order_info['email'],
				'amount'	=> (float)$mobipaid_order['amount']
			);

			$this->log('refundPayment - payment ID : ' . $payment_id);
			$this->log('refundPayment - body : ' . print_r($body, true));
			MobipaidApi::$access_key = $this->config->get('payment_mobipaid_access_key');
			$results = MobipaidApi::doRefund($payment_id, $body);
			$results = json_decode($results, true);
			$this->log('refundPayment - result : ' . print_r($results, true));

			if ('refund' === $results['status']) {
				$payment_result['successMessage'] = 'SUCCESS_MOBIPAID_REFUND_PAYMENT';
				$payment_result['status'] = true;
				return $payment_result;
			}

			$payment_result['successMessage'] = 'FAILED_MOBIPAID_REFUND_PAYMENT';
			$payment_result['status'] = false;
			return $payment_result;
		}
	}

	/**
	 * Save the Mobipaid Order data into the database
	 *
	 * @param array $data
	 * @return  void
	 */
	public function saveOrder($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "payment_mobipaid_orders` (order_id, payment_id, amount, currency, result ) VALUES ('" . (int)$data['order_id'] . "', '" . $data['payment_id'] . "', " . $data['amount'] . ", '" . $data['currency'] . "', '" . $data['result'] . "')");
	}


	/**
	 * this funxtion is get Payment Mobipaid data
	 *
	 * @return boolean | array
	 */
	public function getPaymentMobipaidData($order_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "payment_mobipaid_data WHERE order_id = '" . (int)$order_id . "'");
		if ($query->num_rows) {
			return $query->row;
		}
		return false;
	}


	/**
	 * this function is update Mobipaid Data
	 *
	 * @return Void
	 */
	public function updateMobipaidData($order_id = 0, $params = '', $value = '')
	{
		if (!$this->getPaymentMobipaidData($order_id)) {
			$this->db->query("insert into " . DB_PREFIX . "payment_mobipaid_data (" . $params . ") values('" . $value . "')");
		} else {
			$this->db->query("update " . DB_PREFIX . "payment_mobipaid_data set " . $params . "='" . $value . "' where order_id=" . (int)$order_id);
		}
	}
}
