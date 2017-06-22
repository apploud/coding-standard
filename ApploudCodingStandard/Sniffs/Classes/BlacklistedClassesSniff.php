<?php declare(strict_types = 1);

namespace ApploudCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\ReferencedNameHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

class BlacklistedClassesSniff implements Sniff
{

	private const CODE_BLACKLISTED_CLASS_USE = 'BlacklistedClassUse';

	private const CODE_BLACKLISTED_CLASS_REFERENCE = 'BlacklistedClassReference';

	/** @var string[] */
	public $blacklistedClasses = [];

	/** @var string[] */
	private $normalizedBlacklistedClasses;

	/**
	 * @return int[]
	 */
	public function register(): array
	{
		return [
			T_OPEN_TAG,
		];
	}

	/**
	 * @return string[]
	 */
	private function getBlacklistedClasses(): array
	{
		if ($this->normalizedBlacklistedClasses === null) {
			$this->normalizedBlacklistedClasses = SniffSettingsHelper::normalizeArray($this->blacklistedClasses);
			$this->normalizedBlacklistedClasses = array_map(function (string $className) {
				return NamespaceHelper::normalizeToCanonicalName($className);
			}, $this->normalizedBlacklistedClasses);
		}

		return $this->normalizedBlacklistedClasses;
	}

	/**
	 * @param File $phpcsFile
	 * @param int $openTagPointer
	 * @return void
	 */
	public function process(File $phpcsFile, $openTagPointer): void // @codingStandardsIgnoreLine
	{
		foreach (UseStatementHelper::getUseStatements($phpcsFile, $openTagPointer) as $useStatement) {
			$canonicalName = NamespaceHelper::normalizeToCanonicalName($useStatement->getFullyQualifiedTypeName());
			if (in_array($canonicalName, $this->getBlacklistedClasses(), true)) {
				$phpcsFile->addError(sprintf(
					'Class %s should not be used.',
					$canonicalName
				), $useStatement->getPointer(), self::CODE_BLACKLISTED_CLASS_USE);
			}
		}

		$referencedNames = ReferencedNameHelper::getAllReferencedNames($phpcsFile, $openTagPointer);
		foreach ($referencedNames as $referencedName) {
			$name = $referencedName->getNameAsReferencedInFile();
			$nameStartPointer = $referencedName->getStartPointer();
			$canonicalName = NamespaceHelper::normalizeToCanonicalName($name);

			if (NamespaceHelper::isFullyQualifiedName($name)) {
				if (in_array($canonicalName, $this->getBlacklistedClasses(), true)) {
					$phpcsFile->addError(sprintf(
						'Class %s should not be referenced.',
						$canonicalName
					), $nameStartPointer, self::CODE_BLACKLISTED_CLASS_REFERENCE);
				}
			}
			// TODO: partial uses: elseif (NamespaceHelper::isQualifiedName($name)
		}
	}

}
