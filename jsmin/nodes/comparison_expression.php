<?php
class ComparisonExpression extends BinaryExpression {
	public function visit(AST $ast) {
		parent::visit($ast);

		return $this;
	}

	public function toString() {
		if (!is_object($this->left) && $this->right) {
			echo $this->right->toString() . "\n";
		}

		return $this->group($this, $this->left) . $this->type . $this->group($this, $this->right, false);
	}

	public function type() {
		return 'boolean';
	}

	public function precedence() {
		return 10;
	}

	public function negate() {
		$map = array(
			OP_LE => OP_GT,
			OP_GE => OP_LT,
			OP_LT => OP_GE,
			OP_GT => OP_LE
		);

		if (isset($map[$this->type])) {
			return new ComparisonExpression($map[$this->type], $this->left, $this->right);
		}

		return parent::negate();
	}
}
