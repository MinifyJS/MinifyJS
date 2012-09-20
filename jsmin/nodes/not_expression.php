<?php
class NotExpression extends UnaryExpression {
	public function visit(AST $ast, Node $parent = null) {
		$that = new NotExpression($this->left->visit($ast, $this));

		if (null !== $result = $that->asBoolean()) {
			$result = new Boolean($result);
			return $result->visit($ast, $parent);
		}

		return AST::bestOption(array(
			$that,
			$that->left->negate()->boolean()
		));
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

	public function mayInline() {
		return $this->left->mayInline();
	}

	public function looseBoolean() {
		if ($this->left instanceof NotExpression) {
			return $this->left->left()->looseBoolean();
		}

		if (null !== $left = $this->asNumber()) {
			return new Number($left);
		}

		return parent::looseBoolean();
	}

	public function optimize() {
		return $this->left;
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
	}


	public function negate() {
		return $this->left;
	}
}