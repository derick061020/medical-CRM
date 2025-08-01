<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\InvalidColumnType\ColumnValuesRequired;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Platforms\Keywords\MySQLKeywords;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Schema\Name\UnquotedIdentifierFolding;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\SQL\Builder\DefaultSelectSQLBuilder;
use Doctrine\DBAL\SQL\Builder\SelectSQLBuilder;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;

use function array_diff;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function sprintf;
use function str_replace;
use function strtolower;

/**
 * Provides the base implementation for the lowest versions of supported MySQL-like database platforms.
 */
abstract class AbstractMySQLPlatform extends AbstractPlatform
{
    final public const LENGTH_LIMIT_TINYTEXT   = 255;
    final public const LENGTH_LIMIT_TEXT       = 65535;
    final public const LENGTH_LIMIT_MEDIUMTEXT = 16777215;

    final public const LENGTH_LIMIT_TINYBLOB   = 255;
    final public const LENGTH_LIMIT_BLOB       = 65535;
    final public const LENGTH_LIMIT_MEDIUMBLOB = 16777215;

    public function __construct()
    {
        parent::__construct(UnquotedIdentifierFolding::NONE);
    }

    protected function doModifyLimitQuery(string $query, ?int $limit, int $offset): string
    {
        if ($limit !== null) {
            $query .= sprintf(' LIMIT %d', $limit);

            if ($offset > 0) {
                $query .= sprintf(' OFFSET %d', $offset);
            }
        } elseif ($offset > 0) {
            // 2^64-1 is the maximum of unsigned BIGINT, the biggest limit possible
            $query .= sprintf(' LIMIT 18446744073709551615 OFFSET %d', $offset);
        }

        return $query;
    }

    public function quoteSingleIdentifier(string $str): string
    {
        return '`' . str_replace('`', '``', $str) . '`';
    }

    public function getRegexpExpression(): string
    {
        return 'RLIKE';
    }

    public function getLocateExpression(string $string, string $substring, ?string $start = null): string
    {
        if ($start === null) {
            return sprintf('LOCATE(%s, %s)', $substring, $string);
        }

        return sprintf('LOCATE(%s, %s, %s)', $substring, $string, $start);
    }

    public function getConcatExpression(string ...$string): string
    {
        return sprintf('CONCAT(%s)', implode(', ', $string));
    }

    protected function getDateArithmeticIntervalExpression(
        string $date,
        string $operator,
        string $interval,
        DateIntervalUnit $unit,
    ): string {
        $function = $operator === '+' ? 'DATE_ADD' : 'DATE_SUB';

        return $function . '(' . $date . ', INTERVAL ' . $interval . ' ' . $unit->value . ')';
    }

    public function getDateDiffExpression(string $date1, string $date2): string
    {
        return 'DATEDIFF(' . $date1 . ', ' . $date2 . ')';
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'DATABASE()';
    }

    public function getLengthExpression(string $string): string
    {
        return 'CHAR_LENGTH(' . $string . ')';
    }

    /** @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy. */
    public function getListDatabasesSQL(): string
    {
        return 'SHOW DATABASES';
    }

    /** @internal The method should be only used from within the {@see AbstractSchemaManager} class hierarchy. */
    public function getListViewsSQL(string $database): string
    {
        return 'SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ' . $this->quoteStringLiteral($database);
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonTypeDeclarationSQL(array $column): string
    {
        if (! empty($column['jsonb'])) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6939',
                'The "jsonb" column platform option is deprecated. Use the "JSONB" type instead.',
            );
        }

        return 'JSON';
    }

    /**
     * Gets the SQL snippet used to declare a CLOB column type.
     *     TINYTEXT   : 2 ^  8 - 1 = 255
     *     TEXT       : 2 ^ 16 - 1 = 65535
     *     MEDIUMTEXT : 2 ^ 24 - 1 = 16777215
     *     LONGTEXT   : 2 ^ 32 - 1 = 4294967295
     *
     * {@inheritDoc}
     */
    public function getClobTypeDeclarationSQL(array $column): string
    {
        if (! empty($column['length']) && is_numeric($column['length'])) {
            $length = $column['length'];

            if ($length <= static::LENGTH_LIMIT_TINYTEXT) {
                return 'TINYTEXT';
            }

            if ($length <= static::LENGTH_LIMIT_TEXT) {
                return 'TEXT';
            }

            if ($length <= static::LENGTH_LIMIT_MEDIUMTEXT) {
                return 'MEDIUMTEXT';
            }
        }

        return 'LONGTEXT';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeTypeDeclarationSQL(array $column): string
    {
        if (isset($column['version']) && $column['version'] === true) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6940',
                'The "version" column platform option is deprecated.',
            );

            return 'TIMESTAMP';
        }

        return 'DATETIME';
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTypeDeclarationSQL(array $column): string
    {
        return 'DATE';
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeTypeDeclarationSQL(array $column): string
    {
        return 'TIME';
    }

    /**
     * {@inheritDoc}
     */
    public function getBooleanTypeDeclarationSQL(array $column): string
    {
        return 'TINYINT(1)';
    }

    /**
     * {@inheritDoc}
     *
     * MySQL supports this through AUTO_INCREMENT columns.
     */
    public function supportsIdentityColumns(): bool
    {
        return true;
    }

    /** @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy. */
    public function supportsInlineColumnComments(): bool
    {
        return true;
    }

    /** @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy. */
    public function supportsColumnCollation(): bool
    {
        return true;
    }

    /**
     * The SQL snippet required to elucidate a column type
     *
     * Returns a column type SELECT snippet string
     *
     * @internal The method should be only used from within the {@see MySQLSchemaManager} class hierarchy.
     */
    public function getColumnTypeSQLSnippet(string $tableAlias, string $databaseName): string
    {
        return $tableAlias . '.DATA_TYPE';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCreateTableSQL(string $name, array $columns, array $options = []): array
    {
        $this->validateCreateTableOptions($options, __METHOD__);

        $queryFields = $this->getColumnDeclarationListSQL($columns);

        if (! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $definition) {
                $queryFields .= ', ' . $this->getUniqueConstraintDeclarationSQL($definition);
            }
        }

        // add all indexes
        if (! empty($options['indexes'])) {
            foreach ($options['indexes'] as $definition) {
                $queryFields .= ', ' . $this->getIndexDeclarationSQL($definition);
            }
        }

        // attach all primary keys
        if (! empty($options['primary'])) {
            $keyColumns   = array_unique(array_values($options['primary']));
            $queryFields .= ', PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
        }

        $sql = ['CREATE'];

        if (! empty($options['temporary'])) {
            $sql[] = 'TEMPORARY';
        }

        $sql[] = 'TABLE ' . $name . ' (' . $queryFields . ')';

        $tableOptions = $this->buildTableOptions($options);

        if ($tableOptions !== '') {
            $sql[] = $tableOptions;
        }

        if (isset($options['partition_options'])) {
            $sql[] = $options['partition_options'];
        }

        $sql = [implode(' ', $sql)];

        if (isset($options['foreignKeys'])) {
            foreach ($options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $name);
            }
        }

        return $sql;
    }

    public function createSelectSQLBuilder(): SelectSQLBuilder
    {
        return new DefaultSelectSQLBuilder($this, 'FOR UPDATE', null);
    }

    /**
     * Build SQL for table options
     *
     * @param mixed[] $options
     */
    private function buildTableOptions(array $options): string
    {
        if (isset($options['table_options'])) {
            return $options['table_options'];
        }

        $tableOptions = [];

        if (isset($options['charset'])) {
            $tableOptions[] = sprintf('DEFAULT CHARACTER SET %s', $options['charset']);
        }

        if (isset($options['collation'])) {
            $tableOptions[] = $this->getColumnCollationDeclarationSQL($options['collation']);
        }

        if (isset($options['engine'])) {
            $tableOptions[] = sprintf('ENGINE = %s', $options['engine']);
        }

        // Auto increment
        if (isset($options['auto_increment'])) {
            $tableOptions[] = sprintf('AUTO_INCREMENT = %s', $options['auto_increment']);
        }

        // Comment
        if (isset($options['comment'])) {
            $tableOptions[] = sprintf('COMMENT = %s ', $this->quoteStringLiteral($options['comment']));
        }

        // Row format
        if (isset($options['row_format'])) {
            $tableOptions[] = sprintf('ROW_FORMAT = %s', $options['row_format']);
        }

        return implode(' ', $tableOptions);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlterTableSQL(TableDiff $diff): array
    {
        $queryParts = [];

        foreach ($diff->getAddedColumns() as $column) {
            $columnProperties = array_merge($column->toArray(), [
                'comment' => $column->getComment(),
            ]);

            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL(
                $column->getQuotedName($this),
                $columnProperties,
            );
        }

        foreach ($diff->getDroppedColumns() as $column) {
            $queryParts[] =  'DROP ' . $column->getQuotedName($this);
        }

        foreach ($diff->getChangedColumns() as $columnDiff) {
            $newColumn = $columnDiff->getNewColumn();

            $newColumnProperties = array_merge($newColumn->toArray(), [
                'comment' => $newColumn->getComment(),
            ]);

            $oldColumn = $columnDiff->getOldColumn();

            $queryParts[] =  'CHANGE ' . $oldColumn->getQuotedName($this) . ' '
                . $this->getColumnDeclarationSQL($newColumn->getQuotedName($this), $newColumnProperties);
        }

        $droppedIndexes = $this->indexIndexesByLowerCaseName($diff->getDroppedIndexes());
        $addedIndexes   = $this->indexIndexesByLowerCaseName($diff->getAddedIndexes());
        $diffModified   = false;

        $noLongerPrimaryKeyColumns = [];

        if (isset($droppedIndexes['primary'])) {
            $queryParts[] = 'DROP PRIMARY KEY';

            $noLongerPrimaryKeyColumns = $droppedIndexes['primary']->getColumns();
        }

        if (isset($addedIndexes['primary'])) {
            $keyColumns   = array_values(array_unique($addedIndexes['primary']->getColumns()));
            $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';

            $noLongerPrimaryKeyColumns = array_diff(
                $noLongerPrimaryKeyColumns,
                $addedIndexes['primary']->getColumns(),
            );

            $diff->unsetAddedIndex($addedIndexes['primary']);
        }

        $tableSql = [];

        if (isset($droppedIndexes['primary'])) {
            $oldTable = $diff->getOldTable();
            foreach ($noLongerPrimaryKeyColumns as $columnName) {
                if (! $oldTable->hasColumn($columnName)) {
                    continue;
                }

                $column = $oldTable->getColumn($columnName);
                if ($column->getAutoincrement()) {
                    $tableSql = array_merge(
                        $tableSql,
                        $this->getPreAlterTableAlterPrimaryKeySQL($diff, $droppedIndexes['primary']),
                    );
                    break;
                }
            }

            $diff->unsetDroppedIndex($droppedIndexes['primary']);
        }

        if (count($queryParts) > 0) {
            $tableSql[] = 'ALTER TABLE ' . $diff->getOldTable()->getQuotedName($this) . ' '
                . implode(', ', $queryParts);
        }

        return array_merge(
            $this->getPreAlterTableIndexForeignKeySQL($diff),
            $tableSql,
            $this->getPostAlterTableIndexForeignKeySQL($diff),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff): array
    {
        $sql = [];

        $tableNameSQL = $diff->getOldTable()->getQuotedName($this);

        foreach ($diff->getModifiedIndexes() as $changedIndex) {
            $sql = array_merge($sql, $this->getPreAlterTableAlterPrimaryKeySQL($diff, $changedIndex));
        }

        foreach ($diff->getDroppedIndexes() as $droppedIndex) {
            $sql = array_merge($sql, $this->getPreAlterTableAlterPrimaryKeySQL($diff, $droppedIndex));

            foreach ($diff->getAddedIndexes() as $addedIndex) {
                if ($droppedIndex->getColumns() !== $addedIndex->getColumns()) {
                    continue;
                }

                $indexClause = 'INDEX ' . $addedIndex->getName();

                if ($addedIndex->isPrimary()) {
                    $indexClause = 'PRIMARY KEY';
                } elseif ($addedIndex->isUnique()) {
                    $indexClause = 'UNIQUE INDEX ' . $addedIndex->getName();
                }

                $query  = 'ALTER TABLE ' . $tableNameSQL . ' DROP INDEX ' . $droppedIndex->getName() . ', ';
                $query .= 'ADD ' . $indexClause;
                $query .= ' (' . implode(', ', $addedIndex->getQuotedColumns($this)) . ')';

                $sql[] = $query;

                $diff->unsetAddedIndex($addedIndex);
                $diff->unsetDroppedIndex($droppedIndex);

                break;
            }
        }

        return array_merge(
            $sql,
            $this->getPreAlterTableAlterIndexForeignKeySQL($diff),
            parent::getPreAlterTableIndexForeignKeySQL($diff),
            $this->getPreAlterTableRenameIndexForeignKeySQL($diff),
        );
    }

    /** @return list<string> */
    private function getPreAlterTableAlterPrimaryKeySQL(TableDiff $diff, Index $index): array
    {
        if (! $index->isPrimary()) {
            return [];
        }

        $table = $diff->getOldTable();

        $sql = [];

        $tableNameSQL = $table->getQuotedName($this);

        // Dropping primary keys requires to unset autoincrement attribute on the particular column first.
        foreach ($index->getColumns() as $columnName) {
            if (! $table->hasColumn($columnName)) {
                continue;
            }

            $column = $table->getColumn($columnName);

            if (! $column->getAutoincrement()) {
                continue;
            }

            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6841',
                'Relying on the auto-increment attribute of a column being automatically dropped once a column'
                    . ' is no longer part of the primary key constraint is deprecated. Instead, drop the auto-increment'
                    . ' attribute explicitly.',
            );

            $column->setAutoincrement(false);

            $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' MODIFY ' .
                $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());

            // original autoincrement information might be needed later on by other parts of the table alteration
            $column->setAutoincrement(true);
        }

        return $sql;
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return list<string>
     */
    private function getPreAlterTableAlterIndexForeignKeySQL(TableDiff $diff): array
    {
        $table = $diff->getOldTable();

        $primaryKey = $table->getPrimaryKey();

        if ($primaryKey === null) {
            return [];
        }

        $primaryKeyColumns = [];

        foreach ($primaryKey->getColumns() as $columnName) {
            if (! $table->hasColumn($columnName)) {
                continue;
            }

            $primaryKeyColumns[] = $table->getColumn($columnName);
        }

        if (count($primaryKeyColumns) === 0) {
            return [];
        }

        $sql = [];

        $tableNameSQL = $table->getQuotedName($this);

        foreach ($diff->getModifiedIndexes() as $changedIndex) {
            // Changed primary key
            if (! $changedIndex->isPrimary()) {
                continue;
            }

            foreach ($primaryKeyColumns as $column) {
                // Check if an autoincrement column was dropped from the primary key.
                if (! $column->getAutoincrement() || in_array($column->getName(), $changedIndex->getColumns(), true)) {
                    continue;
                }

                // The autoincrement attribute needs to be removed from the dropped column
                // before we can drop and recreate the primary key.
                $column->setAutoincrement(false);

                $sql[] = 'ALTER TABLE ' . $tableNameSQL . ' MODIFY ' .
                    $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());

                // Restore the autoincrement attribute as it might be needed later on
                // by other parts of the table alteration.
                $column->setAutoincrement(true);
            }
        }

        return $sql;
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return list<string>
     */
    protected function getPreAlterTableRenameIndexForeignKeySQL(TableDiff $diff): array
    {
        return [];
    }

    protected function getCreateIndexSQLFlags(Index $index): string
    {
        $type = '';
        if ($index->isUnique()) {
            $type .= 'UNIQUE ';
        } elseif ($index->hasFlag('fulltext')) {
            $type .= 'FULLTEXT ';
        } elseif ($index->hasFlag('spatial')) {
            $type .= 'SPATIAL ';
        }

        return $type;
    }

    /**
     * {@inheritDoc}
     */
    public function getIntegerTypeDeclarationSQL(array $column): string
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getBigIntTypeDeclarationSQL(array $column): string
    {
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $column): string
    {
        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getFloatDeclarationSQL(array $column): string
    {
        return 'DOUBLE PRECISION' . $this->getUnsignedDeclaration($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getSmallFloatDeclarationSQL(array $column): string
    {
        return 'FLOAT' . $this->getUnsignedDeclaration($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getDecimalTypeDeclarationSQL(array $column): string
    {
        return parent::getDecimalTypeDeclarationSQL($column) . $this->getUnsignedDeclaration($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnumDeclarationSQL(array $column): string
    {
        if (! isset($column['values']) || ! is_array($column['values']) || $column['values'] === []) {
            throw ColumnValuesRequired::new($this, 'ENUM');
        }

        return sprintf('ENUM(%s)', implode(', ', array_map(
            $this->quoteStringLiteral(...),
            $column['values'],
        )));
    }

    /**
     * Get unsigned declaration for a column.
     *
     * @param mixed[] $columnDef
     */
    private function getUnsignedDeclaration(array $columnDef): string
    {
        return ! empty($columnDef['unsigned']) ? ' UNSIGNED' : '';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $column): string
    {
        $sql = $this->getUnsignedDeclaration($column);

        if (! empty($column['autoincrement'])) {
            $sql .= ' AUTO_INCREMENT';
        }

        return $sql;
    }

    /** @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy. */
    public function getColumnCharsetDeclarationSQL(string $charset): string
    {
        return 'CHARACTER SET ' . $charset;
    }

    /** @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy. */
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey): string
    {
        $query = '';
        if ($foreignKey->hasOption('match')) {
            $query .= ' MATCH ' . $foreignKey->getOption('match');
        }

        $query .= parent::getAdvancedForeignKeyOptionsSQL($foreignKey);

        return $query;
    }

    public function getDropIndexSQL(string $name, string $table): string
    {
        return 'DROP INDEX ' . $name . ' ON ' . $table;
    }

    /**
     * The `ALTER TABLE ... DROP CONSTRAINT` syntax is only available as of MySQL 8.0.19.
     *
     * @link https://dev.mysql.com/doc/refman/8.0/en/alter-table.html
     */
    public function getDropUniqueConstraintSQL(string $name, string $tableName): string
    {
        return $this->getDropIndexSQL($name, $tableName);
    }

    public function getSetTransactionIsolationSQL(TransactionIsolationLevel $level): string
    {
        return 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSQL($level);
    }

    protected function initializeDoctrineTypeMappings(): void
    {
        $this->doctrineTypeMapping = [
            'bigint'     => Types::BIGINT,
            'binary'     => Types::BINARY,
            'blob'       => Types::BLOB,
            'char'       => Types::STRING,
            'date'       => Types::DATE_MUTABLE,
            'datetime'   => Types::DATETIME_MUTABLE,
            'decimal'    => Types::DECIMAL,
            'double'     => Types::FLOAT,
            'enum'       => Types::ENUM,
            'float'      => Types::SMALLFLOAT,
            'int'        => Types::INTEGER,
            'integer'    => Types::INTEGER,
            'json'       => Types::JSON,
            'longblob'   => Types::BLOB,
            'longtext'   => Types::TEXT,
            'mediumblob' => Types::BLOB,
            'mediumint'  => Types::INTEGER,
            'mediumtext' => Types::TEXT,
            'numeric'    => Types::DECIMAL,
            'real'       => Types::FLOAT,
            'set'        => Types::SIMPLE_ARRAY,
            'smallint'   => Types::SMALLINT,
            'string'     => Types::STRING,
            'text'       => Types::TEXT,
            'time'       => Types::TIME_MUTABLE,
            'timestamp'  => Types::DATETIME_MUTABLE,
            'tinyblob'   => Types::BLOB,
            'tinyint'    => Types::BOOLEAN,
            'tinytext'   => Types::TEXT,
            'varbinary'  => Types::BINARY,
            'varchar'    => Types::STRING,
            'year'       => Types::DATE_MUTABLE,
        ];
    }

    /** @deprecated */
    protected function createReservedKeywordsList(): KeywordList
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6607',
            '%s is deprecated.',
            __METHOD__,
        );

        return new MySQLKeywords();
    }

    /**
     * {@inheritDoc}
     *
     * MySQL commits a transaction implicitly when DROP TABLE is executed, however not
     * if DROP TEMPORARY TABLE is executed.
     */
    public function getDropTemporaryTableSQL(string $table): string
    {
        return 'DROP TEMPORARY TABLE ' . $table;
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     *     TINYBLOB   : 2 ^  8 - 1 = 255
     *     BLOB       : 2 ^ 16 - 1 = 65535
     *     MEDIUMBLOB : 2 ^ 24 - 1 = 16777215
     *     LONGBLOB   : 2 ^ 32 - 1 = 4294967295
     *
     * {@inheritDoc}
     */
    public function getBlobTypeDeclarationSQL(array $column): string
    {
        if (! empty($column['length']) && is_numeric($column['length'])) {
            $length = $column['length'];

            if ($length <= static::LENGTH_LIMIT_TINYBLOB) {
                return 'TINYBLOB';
            }

            if ($length <= static::LENGTH_LIMIT_BLOB) {
                return 'BLOB';
            }

            if ($length <= static::LENGTH_LIMIT_MEDIUMBLOB) {
                return 'MEDIUMBLOB';
            }
        }

        return 'LONGBLOB';
    }

    public function quoteStringLiteral(string $str): string
    {
        // MySQL requires backslashes to be escaped as well.
        $str = str_replace('\\', '\\\\', $str);

        return parent::quoteStringLiteral($str);
    }

    public function getDefaultTransactionIsolationLevel(): TransactionIsolationLevel
    {
        return TransactionIsolationLevel::REPEATABLE_READ;
    }

    /** @deprecated */
    public function supportsColumnLengthIndexes(): bool
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/6886',
            '%s is deprecated.',
            __METHOD__,
        );

        return true;
    }

    public function createSchemaManager(Connection $connection): MySQLSchemaManager
    {
        return new MySQLSchemaManager($connection, $this);
    }

    /**
     * @param array<Index> $indexes
     *
     * @return array<string,Index>
     */
    private function indexIndexesByLowerCaseName(array $indexes): array
    {
        $result = [];

        foreach ($indexes as $index) {
            $result[strtolower($index->getName())] = $index;
        }

        return $result;
    }

    /** @internal The method should be only used from within the {@see MySQLSchemaManager} class hierarchy. */
    public function fetchTableOptionsByTable(bool $includeTableName): string
    {
        $sql = <<<'SQL'
    SELECT t.TABLE_NAME,
           t.ENGINE,
           t.AUTO_INCREMENT,
           t.TABLE_COMMENT,
           t.CREATE_OPTIONS,
           t.TABLE_COLLATION,
           ccsa.CHARACTER_SET_NAME
      FROM information_schema.TABLES t
        INNER JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY ccsa
          ON ccsa.COLLATION_NAME = t.TABLE_COLLATION
SQL;

        $conditions = ['t.TABLE_SCHEMA = ?'];

        if ($includeTableName) {
            $conditions[] = 't.TABLE_NAME = ?';
        }

        $conditions[] = "t.TABLE_TYPE = 'BASE TABLE'";

        return $sql . ' WHERE ' . implode(' AND ', $conditions);
    }
}
