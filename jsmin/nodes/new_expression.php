<?php
class NewExpression extends Expression {
	public function __construct(Expression $a, array $b) {
		$this->left = $a;
		$this->right = $b;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->left = $this->left->visit($ast, $this);
		foreach($this->right as $i => $n) {
			$this->right[$i] = $n->visit($ast, $this);
		}

		$result = null;

		if ($this->left instanceof IdentifierExpression && !$this->left->isLocal()) {
			switch((string)$this->left->value()) {
			case 'Array':
				if (count($this->right) !== 1) {
					$result = new ArrayExpression($this->right);
				}  else {
					$result = new CallExpression($this->left, $this->right);
				}

				break;
			case 'RegExp':
			case 'Function':
			case 'Error':
				$result = new CallExpression($this->left, $this->right);
				break;
			}
		}

		return $result ? $result->visit($ast, $parent) : $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		foreach($this->right as $n) {
			$n->collectStatistics($ast);
		}
	}

	public function gone() {
		$this->left->gone();
		foreach($this->right as $n) {
			$n->gone();
		}
	}

	public function countLetters(&$letters) {
		$letters['n'] += 1;
		$letters['e'] += 1;
		$letters['w'] += 1;

		$this->left->countLetters($letters);
		foreach($this->right as $n) {
			$n->countLetters($letters);
		}
	}

	public function toString() {
		return 'new' . Stream::legalStart($this->group($this, $this->left)) . ($this->right ?  '(' . implode(',', $this->right) . ')' : '');
	}

	public function actualType() {
		if ($this->left instanceof IdentifierExpression && !$this->left->isLocal()) {
			switch ((string)$this->left->value()) {
			case 'Array':
				return 'array';
			case 'RegExp':
				return 'regexp';
			case 'Function':
				return 'function';
			case 'Date':
				return 'date';
			}
		}
	}

	public function precedence() {
		if ($this->right) {
			return 0;
		}

		return 16;
	}
}