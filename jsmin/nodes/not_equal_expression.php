<?php
class NotEqualExpression extends ComparisonExpression {
	protected $strict = false;

	public function __construct(Expression $left, Expression $right, $strict) {
		parent::__construct($strict ? OP_STRICT_NE : OP_NE, $left, $right);
		$this->strict = $strict;
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = new NotEqualExpression(
			$this->left->visit($ast, $this),
			$this->right->visit($ast, $this),
			$this->strict
		);

		if ($that->strict && ($left = $that->left->actualType()) === $that->right->actualType()) {
			if ($left !== null) {
				$that->strict = ComparisonExpression::NOT_STRICT;
				$that->type = OP_NE;
			}
		}

		if ($that->right->asString() === 'undefined' && $that->left instanceof TypeofExpression) {
			if ($that->left->left()->isLocal()) {
				$result = new NotEqualExpression($that->left->left(), new VoidExpression(new Number(0)), ComparisonExpression::STRICT);
				return $result->visit($ast);
			} elseif ((null !== $type = $that->left->left->type())) {
				return new Boolean($type !== 'undefined');
			}
		}

		if ($that->right->isImmutable() && !$that->left->isImmutable()) {
			return AST::bestOption(array(
				new NotEqualExpression($that->right, $that->left, $this->strict),
				$that
			));
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
