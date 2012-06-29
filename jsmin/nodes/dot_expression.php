<?php
class DotExpression extends Expression {
	protected $rightString;

	public function __construct(Expression $left, Identifier $right) {
		$this->left = $left;
		$this->right = $right;

		$this->rightString = $right->toString();

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		if ($this->left instanceof IdentifierExpression && $this->left->value() === 'Number') {
			switch ($this->right->name()) {
			case 'NaN':
				return new DivExpression(new Number(0), new Number(0));
			case 'POSITIVE_INFINITY':
				return new DivExpression(new Number(1), new Number(0));
			case 'NEGATIVE_INFINITY':
				return new DivExpression(new Number(1), new UnaryMinusExpression(new Number(0)));
			}
		}

		return $this;
	}

	public function gone() {
		$this->left->gone();
	}

	public function collectStatistics(AST $ast, $write = false) {
		$this->left->collectStatistics($ast, $write);
	}

	public function isLocal() {
		return $this->left->isLocal();
	}

	public function mayInline() {
		if (!AST::$options['unsafe']) {
			return false;
		}

		return $this->left->mayInline();
	}

	public function toString() {
		$left = $this->group($this, $this->left);

		// 5.fn() is a syntax error, so fix it up
		if ($this->left instanceof Number && preg_match('~^(?:[1-9]\d*|0)$~', $left)) {
			$left .= '.';
		}

		return $left . '.' . $this->rightString;
	}

	public function isRedundant() {
		return $this->left->isRedundant();
	}

	public function isConstant() {
		return false;
	}

	public function precedence() {
		return 17;
	}
}
