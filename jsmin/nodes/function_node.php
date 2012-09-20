<?php
class FunctionNode extends Node {
	protected $name;
	protected $params;
	protected $body;
	protected $functionForm;

	public function __construct($name, array $params, ScriptNode $body, $form) {
		$this->name = $name;
		$this->params = $params;
		$this->body = $body;
		$this->functionForm = $form;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		if ($ast->hasStats() && $this->name) {
			if ($this->functionForm !== EXPRESSED_FORM && !$this->name->keep(1)) {
				$this->gone();

				return new VoidExpression(new Number(0));
			}
		}

		if ($this->name) {
			$this->name = $this->name->visit($ast, $this);
		}

		foreach($this->params as $i => $p) {
			$p->write();
			$this->params[$i] = $p->visit($ast);
		}

		$this->body = $this->body->visit($ast, $this);

		return $this;
	}

	public function collectStatistics(AST $ast) {
		if ($this->name) {
			$this->name->collectStatistics($ast);
		}

		foreach($this->params as $p) {
			$p->collectStatistics($ast, true);
		}

		$this->body->collectStatistics($ast);
	}

	public function toString() {
		$space = AST::$options['beautify'] ? ' ' : '';

		return 'function' . ($this->name && ($this->functionForm !== EXPRESSED_FORM || $this->name->used() > 1) ? ' ' . $this->name->toString() : $space)
			. '(' . implode(',' . $space, $this->params) . ')' . $space
			. $this->body->asBlock()->toString(false, false);
	}

	public function onlyReturns() {
		foreach ($this->params as $param) {
			if ($param->used() > 1) {
				return null;
			}
		}

		$n = $this->body->isSingle();

		if ($n instanceof ReturnNode) {
			return $n->value();
		} elseif ($n instanceof Expression) {
			return new VoidExpression($n);
		}

		return null;
	}

	public function optimizeArguments() {
		for ($i = count($this->params); $i--; ) {
			if ($this->params[$i]->used() < 2) {
				array_splice($this->params, $i);
			} else {
				break;
			}
		}
	}

	public function gone() {
		$this->body->gone();
		if ($this->name) {
			$this->name->used(false);
		}
	}

	public function countLetters(&$letters) {
		foreach(array('f', 'u', 'n', 'c', 't', 'i', 'o', 'n') as $l) {
			$letters[$l] += 1;
		}

		if ($this->name && ($this->functionForm !== EXPRESSED_FORM || $this->name->used() > 1)) {
			$this->name->countLetters($letters);
		}

		$this->body->countLetters($letters);
	}

	public function debug() {
		return 'function ' . ($this->name ? $this->name->debug() : '') . '( ' . implode(',', $this->params) . ') ' . $this->body->asBlock()->toString(false);
	}
}
