<?php
class RegExp extends ConstantExpression {
	protected $flags = '';

	public function __construct($left) {
		parent::__construct($left);

		$tmp = explode('/', $left);
		$this->flags = end($tmp);
	}

	public function toString() {
		return $this->left;
	}

	public function hasFlag($flag) {
		foreach(str_split($flag) as $flag) {
			if (false === strpos($this->flags, $flag)) {
				return false;
			}
		}

		return true;
	}

	public function visit(AST $ast) {
		return $this;
	}

	public function actualType() {
		return 'regexp';
	}

	public function removeUseless() {
		return new VoidExpression(new Number(0));
	}

	public function countLetters(&$letters) {
		foreach(array_keys($letters) as $letter) {
			$letters[$letter] += substr_count($this->left, $letter);
		}
	}

	public function mayInline() {
		return false;
	}
}
