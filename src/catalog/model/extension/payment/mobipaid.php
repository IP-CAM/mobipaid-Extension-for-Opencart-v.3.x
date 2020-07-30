<?php
/* Mobipaid model
 *
 * @version 1.0.01
 * @date 2018-09-26
 *
 */
include_once(dirname(__FILE__) . '/../../mobipaid/mobipaid.php');

class ModelExtensionPaymentMobipaid extends ModelMobipaidMobipaid
{
	/**
	 * this variable is Code
	 *
	 * @var string $code
	 */
	protected $code = 'mobipaid';

	/**
	 * this variable is title
	 *
	 * @var string $title
	 */
	protected $title = 'FRONTEND_PM_MOBIPAID';

	/**
	 * this variable is logo
	 *
	 * @var string $logo
	 */
	protected $logo = 'mobipaid.png';
}
