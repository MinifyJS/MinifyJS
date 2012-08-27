<?php
class VarDeclarationsNode extends Node {
	public function __construct(array $decs) {
		foreach($decs as $dec) {
			if (!$dec instanceof VarNode) {
				throw new InvalidArgumentException($dec->toString() . ' is no VarNode');
			}
		}

		$this->nodes = $decs;

		parent::__construct();
	}

	public function visit(AST $ast) {
		foreach($this->nodes as $i => $e) {
			$this->nodes[$i] = $e->visit($ast);
		}

		return $this;
	}

	public function count() {
		return count($this->nodes);
	}

	public function add(VarNode $n) {
		if ($this->has($n->name())) {
			throw new Exception('Adding duplicate var-decl');
		}

		$this->nodes[] = $n;
		return $this;
	}

	public function has(IdentifierExpression $x) {
		foreach($this->nodes as $n) {
			if ($n->name()->value() === $x->value()) {
				return true;
			}
		}

		false;
	}

	public function collectStatistics(AST $ast) {
		foreach($this->nodes as $d) {
			$d->collectStatistics($ast);
		}
	}

	public function merge(VarDeclarationsNode $n) {
		foreach($this->nodes as $x) {
			if ($n->has($x->name())) {
				return false;
			}
		}

		// put them in in reversed order
		foreach(array_reverse($this->nodes) as $x) {
			array_unshift($n->nodes, $x);
		}

		return true;
	}

	public function toString($noIn = false) {
		$o = array();
		foreach($this->nodes as $d) {
			if ($d instanceof VarNode) {
				if ($a = substr($d->toString(false, $noIn), 4)) {
					$o[] = $a;
				}
			}
		}

		return $o ? 'var ' . join(',', $o) : '';
	}

	public function gone() {
		foreach($this->nodes as $n) {
			$n->gone();
		}
	}

	public function nodes() {
		return $this->nodes;
	}
}
