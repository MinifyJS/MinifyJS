<?php
class ModExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_MOD, $left, $right);
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = parent::visit($ast, $parent);

		// division can be messy (1/3 = 0.333…)
		if (null !== $result = $this->asNumber()) {
			return AST::bestOption(array(new Number($result), $that));
		}

		return $that;
	}

	public function asNumber() {
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			if ($right == 0) {
				return null;
			}

			// only work with integers for now…
			if ((float)(int)$left === (float)$left && (float)(int)$right === (float)$right) {
				return bcmod($left, $right);
			}
		}
	}

	public function toString() {
		return $this->binary('%');
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 13;
	}
}
