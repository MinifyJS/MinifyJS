<?php
class Number extends ConstantExpression {
	public function __construct($value) {
		parent::__construct(eval('return ' . $value . ';'));
	}

	public function value() {
		return $this->left;
	}

	public function toString() {
		$t = $this->left;

		$a = array(preg_replace('~^0+\.~', '.', $t));
		if (floor($t) == $t) {
			$sign = $t >= 0 ? '' : '-';

			$a[] = $sign . '0x' . base_convert($t, 10, 16);
			$a[] = $sign . '0' . base_convert($t, 10, 8);

			if (preg_match('~^(.*?)(0{3,})$~', $t, $m)) {
				$a[] = $m[1] . 'e' . strlen($m[2]);
			}
		} elseif (preg_match('~^0*\.(0+)(.*)~', $t, $m)) {
			$a[] = $m[2] . 'e-' . (strlen($m[1]) + strlen($m[2]));
		}

		return AST::bestOption($a);
	}


	public function type() {
		return 'number';
	}

	public function asBoolean() {
		return (string)$this->left != '0';
	}

	public function asNumber() {
		return $this->value();
	}

	public function asString() {
		return $this->value();
	}

	public function removeUseless() {
		return new VoidExpression(new Number(0));
	}
}