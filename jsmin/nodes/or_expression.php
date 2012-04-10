<?php
class OrExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_OR, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		return $this;
	}

	public function toString() {
		return $this->group($this, $this->left) . '||' . $this->group($this, $this->right, false);
	}

	public function negate() {
		return AST::bestOption(array(
			new NotExpression($this),
			new AndExpression($this->left->negate(), $this->right->negate())
		));
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 4;
	}
}