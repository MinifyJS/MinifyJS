<?php
/**
 *
 */
class Scope {
	static private $prefix = array(
		'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
		'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		'$', '_'
	);
	static private $all = array(
		'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
		'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		'_','0','1','2','3','4','5','6','7','8','9','$'
	);

	public static $reserved = array(
		'break' => 1, 'case' => 1, 'catch' => 1, 'continue' => 1, 'default' => 1, 'delete' => 1, 'do' => 1,
		'else' => 1, 'finally' => 1, 'for' => 1, 'function' => 1, 'if' => 1, 'in' => 1, 'instanceof' => 1,
		'new' => 1, 'return' => 1, 'switch' => 1, 'this' => 1, 'throw' => 1, 'try' => 1, 'typeof' => 1, 'var' => 1,
		'void' => 1, 'while' => 1, 'with' => 1,
		'abstract' => 1, 'boolean' => 1, 'byte' => 1, 'char' => 1, 'class' => 1, 'const' => 1, 'debugger' => 1,
		'double' => 1, 'enum' => 1, 'export' => 1, 'extends' => 1, 'final' => 1, 'float' => 1, 'goto' => 1,
		'implements' => 1, 'import' => 1, 'int' => 1, 'interface' => 1, 'long' => 1, 'native' => 1,
		'package' => 1, 'private' => 1, 'protected' => 1, 'public' => 1, 'short' => 1, 'static' => 1,
		'super' => 1, 'synchronized' => 1, 'throws' => 1, 'transient' => 1, 'volatile' => 1,
		'arguments' => 1, 'eval' => 1, 'true' => 1, 'false' => 1, 'Infinity' => 1, 'NaN' => 1, 'null' => 1, 'undefined' => 1
	);

	static private $prefixSize = 54;
	static private $allSize = 64;

	protected $program;
	protected $parent;

	protected $declared = array();

	protected $nameIndex;

	public function __construct(AST $program, Scope $parent = null) {
		$this->program = $program;
		$this->parent = $parent;
	}

	public function parent() {
		return $this->parent;
	}

	public function exists($name) {
		if (isset($this->declared[$name])) {
			return $this->declared[$name];
		} elseif($this->parent) {
			return $this->parent->exists($name);
		}

		return null;
	}

	public function find($name, $declare = false) {
		if (!$declare) {
			if (!isset($this->declared[$name])) {
				if (!$this->parent) {
					return $this->gen($name, false);
				}

				return $this->parent->find($name);
			}

			return $this->declared[$name];
		} else {
			$find = $this->exists($name);

			return $this->gen($name, true, $find);
		}
	}

	public function declared($name) {
		$n = $this;
		while ($n->parent) {
			$n = $n->parent;
		}

		return isset($n->declared[$name]);
	}

	public function gen($origName, $declare = true, Identifier $linkedTo = null) {
		if ($origName instanceof Identifier) {
			if ($origName->linkedTo()) {
				return $this->gen($origName->linkedTo());
			}

			if ($this->nameIndex === null) {
				$this->nameIndex = $this->parent ? $this->parent->nameIndex : 0;
			}

			do {
				$name = '';
				$i = $this->nameIndex;

				$name = self::$prefix[$i % self::$prefixSize];
				$i = (int)($i / self::$prefixSize);

				while ($i > 0) {
					--$i;
					$name .= self::$all[$i % self::$allSize];
					$i = (int)($i / self::$allSize);
				}

				++$this->nameIndex;
			} while(isset(self::$reserved[$name]) || $this->declared($name));

			return $name;
		} else {
			$n = new Identifier($this, $origName, $linkedTo);

			if ($declare) {
				$n->mustDeclare();
			}

			$this->declared[$origName] = $n;

			return $n;
		}
	}
}