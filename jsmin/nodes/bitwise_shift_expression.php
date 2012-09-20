<?php
class BitwiseShiftExpression extends BinaryExpression {
	public function __construct($type, Expression $left, Expression $right) {
		parent::__construct($type, $left, $right);
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
		if ((null !== $left = $this->left->asNumber()) && (null !== $right = $this->right->asNumber())) {
			$left = (int)$left;
			$right = (int)$right;

			switch ($this->type) {
			case OP_LSH:
				return $left << $right;
			case OP_RSH:
				return $left >> $right;
			case OP_URSH:
				if ($left > 0) {
					return $left >> $right;
				}

				return (($left & 0x7fffffff) >> $right) | (0x40000000 >> ($right - 1));
			}
		}
	}

	public function toString() {
		return $this->binary($this->type);
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 11;
	}
}
