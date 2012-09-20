<?php
class Boolean extends ConstantExpression {
	public function toString() {
		return $this->left ? 'true' : 'false';
	}

	public function type() {
		return 'boolean';
	}

	public function visit(AST $ast, Node $parent = null) {
		if (!AST::$options['crush-bool']) {
			return $this;
		}

		return new NotExpression(new Number($this->negate()->asNumber()));
	}

	public function asBoolean() {
		return $this->left;
	}

	public function looseBoolean() {
		return new Number($this->asNumber());
	}

	public function asNumber() {
		return $this->left ? 1 : 0;
	}

	public function asString() {
		return $this->left ? 'true' : 'false';
	}

	public function countLetters(&$letters) {
		if (AST::$options['crush-bool']) {
			$letters[$this->left ? 0 : 1] += 1;
		} else {
			$letters['e'] += 1;
			if ($this->left) {
				$letters['t'] += 1;
				$letters['r'] += 1;
				$letters['u'] += 1;
			} else {
				$letters['f'] += 1;
				$letters['a'] += 1;
				$letters['l'] += 1;
				$letters['s'] += 1;
			}
		}
	}

	public function negate() {
		return new Boolean(!$this->left);
	}
}
