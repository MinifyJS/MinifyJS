<?php
class BitwiseNotExpression extends UnaryExpression {
	public function toString() {
		return '~' . $this->group($this, $this->left, false);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		// division can be messy (1/3 = 0.333…)
		if (null !== $result = $this->asNumber()) {
			return AST::bestOption(array(new Number($result), $this));
		}

		return $this;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber())) {
			return ~$left;
		}
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function type() {
		return 'number';
	}
}