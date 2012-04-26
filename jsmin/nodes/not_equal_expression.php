<?php
class NotEqualExpression extends ComparisonExpression {
	protected $strict = false;

	public function __construct(Expression $left, Expression $right, $strict) {
		parent::__construct($strict ? OP_STRICT_NE : OP_NE, $left, $right);
		$this->strict = $strict;
	}

	public function visit(AST $ast) {
		$that = parent::visit($ast);

		if ($that->strict && ($left = $that->left->actualType()) === $that->right->actualType()) {
			if ($left !== null) {
				$that->strict = false;
				$that->type = OP_NE;
			}
		}

		if ($this->right->asString() === 'undefined' && $this->left instanceof TypeofExpression && $this->left->left()->isLocal()) {
			$result = new NotEqualExpression($this->left->left(), new VoidExpression(new Number(0)), true);
			return $result->visit($ast);
		}

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
