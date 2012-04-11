<?php
class AndExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_AND, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		return $this;
	}

	public function toString() {
		return $this->binary('&&');
	}

	public function negate() {
		return AST::bestOption(array(
			new NotExpression($this),
			new OrExpression($this->left->negate(), $this->right->negate())
		));
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 5;
	}
}