<?php
class RegExp extends ConstantExpression {
	public function toString() {
		return $this->left;
	}

	public function visit(AST $ast) {
		return $this;
	}

	public function actualType() {
		return 'regexp';
	}
}

