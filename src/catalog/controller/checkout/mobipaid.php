<?php
/* Mobipaid checkout controller
 *
 * @version 1.0.0
 * @date 2020-05-27
 *
 */
include_once(dirname(__FILE__) . '/../mobipaid/mobipaid.php');

class ControllerCheckoutMobipaid extends ControllerMobipaid {

	/**
	 * this variable is Code
	 *
	 * @var string $code
	 */
	protected $code = "mobipaid";
}
