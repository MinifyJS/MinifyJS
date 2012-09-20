<?php
class MinusExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_PLUS, $left, $right);
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
			return bcsub($left, $right, 100);
		}
	}

	public function toString() {
		if (AST::$options['beautify']) {
			return $this->binary('-');
		}

		$r = $this->group($this, $this->right, false);
		if ($r[0] === '-') {
			$r = ' ' . $r;
		}

		return $this->group($this, $this->left) . '-' . $r;
	}

	public function type() {
		return 'number';
	}

	public function precedence() {
		return 12;
	}
}