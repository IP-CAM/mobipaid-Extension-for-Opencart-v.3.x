<?php
/* Mobipaid payment controller
 *
 * @version 1.0.01
 * @date 2018-09-26
 *
 */
include_once(dirname(__FILE__) . '/../../mobipaid/mobipaid.php');

class ControllerExtensionPaymentMobipaid extends ControllerMobipaid
{

	/**
	 * this variable is Code
	 *
	 * @var string $code
	 */
	protected $code = 'mobipaid';

	/**
	 * this function is the constructor of ControllerMobipaid class
	 *
	 * @return  void
	 */
	public function index()
	{

		return $this->confirmHtml();
	}
}
