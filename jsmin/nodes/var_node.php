<?php
class VarNode extends Node {
	protected $name;
	protected $initializer;

	public function __construct(IdentifierExpression $i, Expression $init = null) {
		$this->name = $i;
		$this->initializer = $init ?: new VoidExpression(new Number(0));

		$this->write();

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->name = $this->name->visit($ast, $this);
		$this->initializer = $this->initializer->visit($ast, $this);

		if ($ast->hasStats()) {
			if (!$this->name->keep(1)) {
				$this->name->gone();

				AST::warn('Dropping unused variable ' . $this->name->value());

				if ($this->initializer) {
					return $this->initializer;
				}

				return new VoidExpression(new Number(0));
			} elseif ($this->initializer && !$this->initializer->isVoid()) {
				if ($this->name->get()->scope()->parent()) {
					$this->name->initializer($this->initializer);
				}
			}
		}

		return $this;
	}

	public function write() {
		return $this->name->write();
	}

	public function unassign() {
		$this->name->reassigned(false);
	}

	public function initializer(Expression $x = null) {
		if ($x !== null) {
			if (!$this->initializer->isVoid()) {
				throw new Exception('Bug: already has initializer');
			}

			$this->initializer = $x;
		}

		return $this->initializer;
	}

	public function name() {
		return $this->name;
	}

	public function gone() {
		$this->name->gone();
		if ($this->initializer) {
			$this->initializer->gone();
		}
	}

	public function declarations() {
		return array($this);
	}

	public function collectStatistics(AST $ast, $write = false) {
		$this->name->collectStatistics($ast, $write);

		if (!$this->initializer->isVoid()) {
			if ($this->name->get()->scope()->parent()) {
				$this->name->initializer($this->initializer);
			}

			$this->initializer->collectStatistics($ast);
		}
	}

	public function countLetters(&$letters) {
		$letters['v'] += 0.5;
		$letters['a'] += 0.5;
		$letters['r'] += 0.5;

		if (!$this->initializer->isVoid()) {
			$this->initializer->countLetters($letters);
		}
	}

	public function toString($force = false, $noIn = false) {
		$init = $this->initializer;

		if (!$force && !$this->name->keep(1) && (!$init || $init->isRedundant())) {
			return '';
		}

		$space = AST::$options['beautify'] ? ' ' : '';

		return 'var ' . $this->name . ($init && !$init->isVoid() ? ($space . '=' . $space . $init->toString($noIn)) : '');
	}
}
