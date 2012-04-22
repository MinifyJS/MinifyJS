<?php
class OrExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_OR, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		$else = $this->left->asBoolean();

		if ($else === true) {
			return $this->left;
		} elseif ($else === false) {
			return $this->right;
		}

		return $this;
	}

	public function toString() {
		return $this->binary('||');
	}

	public function negate() {
		return AST::bestOption(array(
			new AndExpression($this->left->negate(), $this->right->negate()),
			parent::negate()
		));
	}

	public function type() {
		return null;
	}

	public function precedence() {
		return 4;
	}
}