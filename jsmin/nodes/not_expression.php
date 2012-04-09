<?php
class NotExpression extends Expression {
	public function __construct(Expression $left) {
		$this->left = $left;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		if (null !== $result = $this->asBoolean()) {
			return new Boolean($result);
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function toString() {
		return '!' . $this->group($this, $this->left, true);
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

	public function precedence() {
		return 14;
	}
}