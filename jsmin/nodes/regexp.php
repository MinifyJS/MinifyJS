<?php
class RegExp extends ConstantExpression {
	public function toString() {
		return $this->left;
	}
}
