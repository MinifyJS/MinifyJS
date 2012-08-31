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

		// this will be inferred from the expression
		if ($that->actualType() === 'boolean') {
			return AST::bestOption(array(
				$that,
				new NotExpression($that->negate()),
				new NotExpression(new NotExpression($that->negate()->negate()))
			));
		}

		return $this;
	}

	public function toString() {
		return $this->binary('&&');
	}

	public function negate() {
		return AST::bestOption(array(
			new OrExpression($this->left->negate(), $this->right->negate()),
			parent::negate()
		));
	}

	public function optimize() {
		return AST::bestOption(array(
			$this,
			new OrExpression($this->left->negate(), $this->right)
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
