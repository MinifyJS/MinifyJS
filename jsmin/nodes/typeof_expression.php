<?php
class TypeofExpression extends Expression {
	public function __construct(Expression $left) {
		$this->left = $left;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

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

	public function value() {
		return $this->left->type();
	}

	public function toString() {
		return 'typeof' . Stream::legalStart($this->group($this, $this->left, false));
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

	public function removeUseless() {
		return $this->left->removeUseless();
	}
}