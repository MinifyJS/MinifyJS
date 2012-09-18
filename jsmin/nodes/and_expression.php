<?php
class AndExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_AND, $left, $right);
	}

	public function visit(AST $ast) {
		$left = $this->left->visit($ast);
		$right = $this->right()->visit($ast);

		$that = new AndExpression($left, $right);

		$else = $left->asBoolean();

		if ($else === true) {
			$left->gone();
			return $right;
		} elseif ($else === false) {
			$right->gone();
			return $left;
		}

		if ($that->right instanceof AndExpression) {
			$result = new AndExpression(
				new AndExpression($that->left, $that->right->left),
				$that->right->right
			);

			return $result->visit($ast);
		}

		// this will be inferred from the expression
		if ($that->actualType() === 'boolean') {
			return AST::bestOption(array(
				new NotExpression(new NotExpression($that->negate()->negate())),
				new NotExpression($that->negate()),
				$that
			));
		}

		return $that;
	}

	public function toString() {
		return $this->binary('&&');
	}

	public function looseBoolean() {
		if (true === $right = $this->right->asBoolean()) {
			return $this->left->looseBoolean();
		}

		$result = $this->negate()->negate();

		return !($result instanceof self) ? $result->looseBoolean() : $result;
	}

	public function negate() {
		return AST::bestOption(array(
			new OrExpression($this->left->negate(), $this->right->negate()),
			parent::negate()
		));
	}

	public function optimize() {
		return AST::bestOption(array(
			$this->looseBoolean(),
			new OrExpression(
				$this->left->negate()->looseBoolean(),
				$this->right
			)
		));
	}

	public function type() {
		if ((null !== ($type = $this->left->type())) && $type === $this->right->type()) {
			return $type;
		}

		return null;
	}

	public function precedence() {
		return 5;
	}
}
