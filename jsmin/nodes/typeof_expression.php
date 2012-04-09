<?php
class TypeofExpression extends Expression {
	public function __construct(Expression $left) {
		$this->left = $left;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
	}

	public function value() {
		return $this->left->type();
	}

	public function toString() {
		if (!$this->left->hasSideEffects()) {
			$n = $this->left->type();

			if ($n !== null) {
				return "'" . $n . "'";
			}
		}

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
}