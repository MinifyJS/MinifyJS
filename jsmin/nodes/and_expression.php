<?php
class AndExpression extends BinaryExpression {
	public function __construct(Expression $left, Expression $right) {
		parent::__construct(OP_AND, $left, $right);
	}

	public function visit(AST $ast) {
		parent::visit($ast);

		$else = $this->left->asBoolean();

		if ($else === true) {
			return $this->right;
		} elseif ($else === false) {
			return $this->left;
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