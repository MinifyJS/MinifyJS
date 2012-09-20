<?php
class EqualExpression extends ComparisonExpression {
	protected $strict = false;

	public function __construct(Expression $left, Expression $right, $strict) {
		parent::__construct($strict ? OP_STRICT_EQ : OP_EQ, $left, $right);
		$this->strict = $strict;
	}

	public function visit(AST $ast, Node $parent = null) {
		$that = new EqualExpression(
			$this->left->visit($ast, $this),
			$this->right->visit($ast, $this),
			$this->strict
		);

		if ($that->strict && ($left = $that->left->actualType()) === $that->right->actualType()) {
			if ($left !== null) {
				$that->strict = false;
				$that->type = OP_EQ;
			}
		}

		if ($that->right->asString() === 'undefined' && $that->left instanceof TypeofExpression) {
			if ($that->left->left()->isLocal()) {
				$result = new EqualExpression($that->left->left(), new VoidExpression(new Number(0)), true);
				return $result->visit($ast, $parent);
			} elseif ((null !== $type = $that->left->left->type())) {
				return new Boolean($type === 'undefined');
			}
		}

		if ($that->left->type() === 'boolean' || $that->right->type() === 'boolean') {
			$left = $that->left->asBoolean();
			$right = $that->right->asBoolean();

			if ($left !== null && $right !== null) {
				$that->left->gone();
				$that->right->gone();

				$result = new Boolean($left === $right);
				return $result->visit($ast, $parent);
			}
		}

		if ($that->right->isImmutable() && !$that->left->isImmutable()) {
			return AST::bestOption(array(
				new EqualExpression($that->right, $that->left, $that->strict),
				$that
			));
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
