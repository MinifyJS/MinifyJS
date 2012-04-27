<?php
class This extends ConstantExpression {
	public function __construct() {
		parent::__construct(null);
	}

	public function toString() {
		return 'this';
	}

	public function isConstant() {
		return false;
	}

	public function mayInline() {
		return false;
	}
}