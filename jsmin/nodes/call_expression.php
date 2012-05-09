<?php
class CallExpression extends Expression {
	public function __construct(Expression $what, array $args) {
		$this->left = $what;
		$this->right = $args;
		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->left = $this->left->visit($ast);

		foreach($this->right as $i => $r) {
			$this->right[$i] = $r->visit($ast);
		}

		$argc = count($this->right);

		$result = null;

		if (!$this->right && $this->left instanceof DotExpression) {
			if ($this->left->right()->name() === 'toString' && !$argc) {
				$result = new PlusExpression($this->left->left(), new String('', false));
			}
		}

		if (AST::$options['strip-debug'] && $this->left instanceof DotExpression && $this->left->left()->value() === 'console') {
			if (!$this->left->left()->isLocal()) {
				$nodes = $this->right;
				$nodes[] = new VoidExpression(new Number(0));

				$result = new CommaExpression($nodes);
				return $result->visit($ast);
			}
		}

		if ($this->left instanceof IdentifierExpression && !$this->left->isLocal()) {
			switch ($this->left->value()) {
			case 'RegExp':

				// temporarily off...

				// premature break
				$flags = null;
				$regexp = null;

				switch ($argc) {
				case 2:
					if (null === $flags = $this->right[1]->asString()) {
						break;
					}
				case 1:
					if (null === $regexp = $this->right[0]->asString()) {
						break;
					}

					$regexp = '/' . $regexp . '/' . $flags;
					break;
				}

				if ($regexp !== null) {
					$result = new RegExp($regexp);
				}
				break;
			case 'Array':
				if ($argc !== 1) {
					$result = new ArrayExpression($this->right);
				}
				break;
			case 'Boolean':
				if ($argc === 0) {
					return new Boolean(false);
				} else {
					$result = new CommaExpression(array_merge(
						array_slice($this->right, 0, -1),
						new NotExpression(new NotExpression(end($this->right)))
					));
				}

				break;
			case 'String':
				$result = new PlusExpression($this->left, new String('', false));
				break;
			case 'Object':
				if (!$this->right) {
					return new ObjectExpression(array());
				}
				break;
			case 'isNaN':
				if ($argc === 1 && $this->right[0] instanceof IdentifierExpression) {
					return new NotEqualExpression($this->right[0], $this->right[0], true);
				}
			}
		}

		if (!$result && $this->left instanceof DotExpression && AST::$options['unsafe']) {
			// check for array shortening..
			if ($this->left->left()->actualType() === 'array' && $this->left->right()->name() === 'join') {
				if (!$this->right || (count($this->right) === 1 && $this->right[0]->asString() === ',')) {
					$result = new PlusExpression($this->left->left(), new String('', false));
				}
			}
		}

		return $result ? $result->visit($ast) : $this;
	}

	public function collectStatistics(AST $ast) {
		$this->left->collectStatistics($ast);
		foreach($this->right as $r) {
			$r->collectStatistics($ast);
		}
	}

	public function toString() {
		return $this->group($this, $this->left) . '(' . implode(',' . (AST::$options['beautify'] ? ' ' : ''), $this->right) . ')';
	}

	public function precedence() {
		return 17;
	}

	public function isRedundant() {
		return false;
	}

	public function gone() {
		$this->left->gone();
		foreach($this->right as $n) {
			$n->gone();
		}
	}

	public function isConstant() {
		return false;
	}
}
