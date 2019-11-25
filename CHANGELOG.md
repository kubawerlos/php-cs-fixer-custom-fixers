# CHANGELOG for PHP CS Fixer: custom fixers

## [Unreleased]
- Update PHP CS Fixer to v2.16
- Add DataProviderStaticFixer
- Add PhpdocTypesTrimFixer
- Deprecate SingleLineThrowFixer

## v1.16.0 - *2019-10-24*
- Add PhpdocOnlyAllowedAnnotationsFixer
- Feature: OperatorLinebreakFixer - handle object operators

## v1.15.0 - *2019-08-19*
- Add CommentSurroundedBySpacesFixer
- Add DataProviderReturnTypeFixer
- Add NoDuplicatedImportsFixer

## v1.14.0 - *2019-07-25*
- Add DataProviderNameFixer
- Add NoUselessSprintfFixer
- Add PhpUnitNoUselessReturnFixer
- Add SingleLineThrowFixer
- Feature: NoCommentedOutCodeFixer - handle class method

## v1.13.0 - *2019-01-11*
- Update PHP CS Fixer to v2.14
- OperatorLinebreakFixer - respect no whitespace around operator
- OperatorLinebreakFixer - support concatenation operator
- Deprecate PhpdocVarAnnotationCorrectOrderFixer

## v1.12.0 - *2018-12-02*
- Add NoCommentedOutCodeFixer
- Add NoUselessCommentFixer
- Add NullableParamStyleFixer
- Deprecate NoUselessClassCommentFixer
- Deprecate NoUselessConstructorCommentFixer
- Feature: OperatorLinebreakFixer - handle ternary operator
- Fix: NoImportFromGlobalNamespaceFixer - class without  namespace
- Fix: NoUselessClassCommentFixer - comment detection
- Fix: TokenRemover - remove last element of file
- Fix: TokenRemover - remove item in line after code
- Fix: NoImportFromGlobalNamespaceFixer - constant named the same as global imported class

## v1.11.0 - *2018-10-14*
- Add PhpdocParamOrderFixer
- Add InternalClassCasingFixer
- Add SingleSpaceAfterStatementFixer
- Add SingleSpaceBeforeStatementFixer
- Add OperatorLinebreakFixer
- Add MultilineCommentOpeningClosingAloneFixer

## v1.10.0 - *2018-09-19*
- Add NoUnneededConcatenationFixer
- Add PhpdocNoSuperfluousParamFixer
- Deprecate ImplodeCallFixer
- Deprecate NoTwoConsecutiveEmptyLinesFixer

## v1.9.0 - *2018-08-10*
- Add NoNullableBooleanTypeFixer

## v1.8.0 - *2018-08-09*
- Add PhpdocSelfAccessorFixer

## v1.7.0 - *2018-08-06*
- Add NoReferenceInFunctionDefinitionFixer
- Add NoImportFromGlobalNamespaceFixer

## v1.6.0 - *2018-07-22*
- Add ImplodeCallFixer
- Add PhpdocSingleLineVarFixer

## v1.5.0 - *2018-06-26*
- Add NoUselessDoctrineRepositoryCommentFixer

## v1.4.0 - *2018-06-09*
- Add NoDoctrineMigrationsGeneratedCommentFixer

## v1.3.0 - *2018-06-05*
- Add PhpdocVarAnnotationCorrectOrderFixer
- Remove @var without type at the beginning in PhpdocNoIncorrectVarAnnotationFixer

## v1.2.0 - *2018-06-03*
- Add PhpdocNoIncorrectVarAnnotationFixer

## v1.1.0 - *2018-06-02*
- Update PHP CS Fixer to v2.12
- Add NoUselessConstructorCommentFixer
- Add PhpdocParamTypeFixer
- Feature: code coverage
- Feature: create Travis stages
- Feature: verify correctness for PHP CS Fixer (without smote tests)
- Fix: false positive class comment

## v1.0.0 - *2018-05-21*
- Add NoLeadingSlashInGlobalNamespaceFixer
- Add NoPhpStormGeneratedCommentFixer
- Add NoTwoConsecutiveEmptyLinesFixer
- Add NoUselessClassCommentFixer
