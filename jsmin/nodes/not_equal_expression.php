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

		foreach(array(
			array($that->left, $that->right),
			array($that->right, $that->left)
		) as $equate) {
			list($left, $right) = $equate;

			if ($right->asString() === 'undefined' && $left instanceof TypeofExpression) {
				if ($left->left()->isLocal()) {
					$result = new EqualExpression($left->left(), new VoidExpression(new Number(0)), true);
					return $result->visit($ast, $parent);
				} elseif ((null !== $type = $left->left->type())) {
					return new Boolean($type === 'undefined');
				}
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
