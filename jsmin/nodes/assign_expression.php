<?php
class AssignExpression extends Expression {
	protected $type;

	public function __construct($type, Node $lval, Expression $value) {
		$this->type = $type;

		$this->left = $lval;
		$this->right = $value;

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);
		$this->right = $this->right->visit($ast);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		return $this->group($this, $this->left) . $this->type . $this->group($this, $this->right);
	}

	public function type() {
		return $this->right->type();
	}

	public function value() {
		return $this->right->value();
	}

	public function precedence() {
		return 2;
	}

	public function isConstant() {
		return false;
	}

	public function assignType() {
		return $this->type;
	}
}
