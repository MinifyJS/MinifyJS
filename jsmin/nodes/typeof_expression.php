<?php
class TypeofExpression extends Expression {
	public function __construct(Expression $left) {
		$this->left = $left;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);

		if (!$this->left->hasSideEffects()) {
			if (null !== $n = $this->left->type()) {
				return new String($n, false);
			}
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function asString() {
		if (null !== $type = $this->left->type()) {
			return $type;
		}
	}

	public function value() {
		return $this->left->type();
	}

	public function gone() {
		$this->left->gone();
	}

	public function toString() {
		return 'typeof' . Stream::legalStart($this->group($this, $this->left));
	}

	public function isConstant() {
		return $this->left->isConstant();
	}

	public function type() {
		return 'string';
	}

	public function precedence() {
		return 14;
	}

	public function mayInline() {
		return $this->left->mayInline();
	}

	public function hasSideEffects() {
		return $this->left->hasSideEffects();
	}

	public function countLetters(&$letters) {
		foreach(array('t', 'y', 'p', 'e', 'o', 'f') as $l) {
			$letters[$l] += 1;
		}

		$this->left->countLetters($letters);
	}

	public function removeUseless() {
		return $this->left->removeUseless();
	}
}