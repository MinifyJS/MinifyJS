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

	public function actualType() {
		return 'null';
	}

	public function asBoolean() {
		return false;
	}

	public function asString() {
		return 'null';
	}

	public function countLetters(&$letters) {
		foreach(array('n', 'u', 'l', 'l') as $l) {
			$letters[$l] += 1;
		}
	}


	public function negate() {
		return new Boolean(true);
	}
}