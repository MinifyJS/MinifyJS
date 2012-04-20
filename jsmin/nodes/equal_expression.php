<?php
class EqualExpression extends ComparisonExpression {
	protected $strict = false;

	public function __construct(Expression $left, Expression $right, $strict) {
		parent::__construct($strict ? OP_STRICT_EQ : OP_EQ, $left, $right);
		$this->strict = $strict;
	}

	public function visit(AST $ast) {
		$that = parent::visit($ast);

		if ($that->strict && ($left = $that->left->actualType()) === $that->right->actualType()) {
			if ($left !== null) {
				$that->strict = false;
				$that->type = OP_EQ;
			}
		}

		return $that;
	}

	public function precedence() {
		return 9;
	}

	public function negate() {
		return new NotEqualExpression($this->left, $this->right, $this->strict);
	}
}
