<?php
class Number extends ConstantExpression {
	public function __construct($value) {
		if (!is_numeric($value)) {
			throw new Exception($value . ' is not numeric');
		}

		parent::__construct(eval('return ' . $value . ';'));
	}

	public function value() {
		return $this->left;
	}

	public function visit(AST $ast) {
		if (is_nan($this->left)) {
			return new DivExpression(new Number(0), new Number(0));
		}

		return $this;
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

	public function toString() {
		$t = rtrim(preg_replace('~(\.[^0\n]*(?:0+[^0\n]+)*)0+$~', '$1', number_format($this->left, 10, '.', '')), '.');

		if (AST::$options['beautify']) {
			return $t;
		}

		$options = array(preg_replace('~^0\.~', '.', $t));
		if (floor($t) == $t) {
			$sign = $t >= 0 ? '' : '-';

			$options[] = $sign . '0x' . base_convert($t, 10, 16);
			$options[] = $sign . '0' . base_convert($t, 10, 8);

			if (preg_match('~^(.*?)(0{3,})$~', $t, $m)) {
				$options[] = $m[1] . 'e' . strlen($m[2]);
			}
		} elseif (preg_match('~^0\.(0+)(.*)$~', $t, $m)) {
			$options[] = $m[2] . 'e-' . (strlen($m[1]) + strlen($m[2]));
		}

		return AST::bestOption($options);
	}
}