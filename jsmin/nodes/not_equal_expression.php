<?php
class NotEqualExpression extends ComparisonExpression {
	protected $strict = false;

	public function __construct(Expression $left, Expression $right, $strict) {
		parent::__construct($strict ? OP_STRICT_NE : OP_NE, $left, $right);
		$this->strict = $strict;
	}

	public function visit(AST $ast) {
		$that = parent::visit($ast);

		return $that;
	}

	public function type() {
		return 'boolean';
	}

	public function precedence() {
		return 9;
	}

	public function negate() {
		return new EqualExpression($this->left, $this->right, $this->strict);
	}
}
