<?php
class ComparisonExpression extends BinaryExpression {
	const STRICT = true;
	const NOT_STRICT = false;

	public function visit(AST $ast) {
		parent::visit($ast);

		return $this;
	}

	public function isConstant() {
		return $this->left->isConstant() && $this->right->isConstant();
	}

	public function toString() {
		return $this->binary($this->type);
	}

	public function type() {
		return 'boolean';
	}

	public function precedence() {
		return 10;
	}

	public function negate() {
		$options = array(parent::negate());

		if (AST::$options['unsafe']) {
			$map = array(
				OP_LE => OP_GT,
				OP_GE => OP_LT,
				OP_LT => OP_GE,
				OP_GT => OP_LE
			);

			if (isset($map[$this->type])) {
				$options[] = new ComparisonExpression($map[$this->type], $this->left, $this->right);
			}
		}

		return AST::bestOption($options);
	}
}
