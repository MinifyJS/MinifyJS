<?php
class BitwiseNotExpression extends UnaryExpression {
	public function toString() {
		return '~' . $this->group($this, $this->left, false);
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function type() {
		return 'number';
	}
}