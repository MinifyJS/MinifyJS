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

		if ($this->type === '=') {
			// you're a stupid jerk if you do c[i++] = c[i++] + 5;
			if (($this->right instanceof PlusExpression && $type = '+=')
					|| ($this->right instanceof MinusExpression && $type = '-=')
					|| ($this->right instanceof MulExpression && $type = '*=')
					|| ($this->right instanceof DivExpression && $type = '/=')
					|| ($this->right instanceof ModExpression && $type = '%=')) {
				if ($this->right->left()->toString() === $this->left->toString()) {
					$this->type = $type;
					$this->right = $this->right->right();
				}
			}
		}

		return $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast, true);
		$this->right->collectStatistics($ast);
	}

	public function toString() {
		$space = AST::$options['beautify'] ? ' ' : '';

		return $this->group($this, $this->left) . $space . $this->type . $space . $this->group($this, $this->right);
	}

	public function type() {
		if ($this->type !== '+=' || $this->right->type() === 'string') {
			return $this->right->type();
		}
	}

	public function gone() {
		$this->left->gone();
		$this->right->gone();

		$this->left->unassign();
	}

	public function value() {
		if ($this->type === '=') {
			return $this->right->value();
		}
	}

	public function precedence() {
		return 2;
	}

	public function isConstant() {
		return false;
	}

	public function represents() {
		return $this->left;
	}

	public function countLetters(&$letters) {
		$this->left->countLetters($letters);
		$this->right->countLetters($letters);
	}

	public function assignType() {
		return $this->type;
	}
}
