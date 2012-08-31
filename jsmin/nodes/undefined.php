<?php
class Undefined extends ConstantExpression {
	public function __construct() {
		parent::__construct(null);
	}

	public function toString() {
		return '';
	}

	public function type() {
		return 'undefined';
	}

	public function asBoolean() {
		return false;
	}

	public function asString() {
		return 'undefined';
	}

	public function negate() {
		return new Boolean(true);
	}
}