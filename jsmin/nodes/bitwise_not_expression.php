<?php
class BitwiseNotExpression extends UnaryExpression {
	public function toString() {
		return $this->unary('~');
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = parent::visit($ast, $parent);

		// division can be messy (1/3 = 0.333…)
		if (null !== $result = $that->asNumber()) {
			return AST::bestOption(array(new Number($result), $that));
		}

		return $that;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber())) {
			return ~(int)$left;
		}
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function type() {
		return 'number';
	}
}
