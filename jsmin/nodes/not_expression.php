<?php
class NotExpression extends UnaryExpression {
	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		if (null !== $result = $this->asBoolean()) {
			$result = new Boolean($result);
			return $result->visit($ast);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function toString() {
		return '!' . $this->group($this, $this->left);
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function type() {
		return 'boolean';
	}

	public function asBoolean() {
		if (null !== $bool = $this->left->asBoolean()) {
			return !$bool;
		}

		return null;
	}

	public function asString() {
		if (null !== $basis = $this->asBoolean()) {
			return $basis ? 'true' : 'false';
		}

		return null;
	}

	public function asNumber() {
		if (null !== $basis = $this->asBoolean()) {
			return (int)$basis;
		}

		return null;
	}

	public function negate() {
		return $this->left;
	}
}