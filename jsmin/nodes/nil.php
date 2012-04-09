<?php
class Nil extends ConstantExpression {
	public function __construct() {
		parent::__construct(null);
	}

	public function toString() {
		return 'null';
	}

	public function type() {
		return 'object';
	}

	public function asBoolean() {
		return false;
	}
}