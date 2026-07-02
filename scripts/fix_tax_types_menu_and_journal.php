<?php

require_once __DIR__ . '/../app/bootstrap.php';

$db = new Database();
$conn = $db->getConnection();

mysqli_begin_transaction($conn);

try {
	// 1) Ensure tax types exist.
	mysqli_query(
		$conn,
		"INSERT IGNORE INTO TaxTypeTable (TaxTypeCode, Name, Description, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy)
		 VALUES ('INSS','INSS','Contribuicao previdenciaria (Receita Federal)','1','0',NOW(),NOW(),'SYSTEM')"
	);
	mysqli_query(
		$conn,
		"INSERT IGNORE INTO TaxTypeTable (TaxTypeCode, Name, Description, IsActive, IsBlocked, CreatedDateTime, ModifiedDateTime, CreatedBy)
		 VALUES ('SIMPLES','Simples Nacional','Tributacao Simples Nacional (Receita Federal)','1','0',NOW(),NOW(),'SYSTEM')"
	);

	$resultInss = mysqli_query($conn, "SELECT RecId FROM TaxTypeTable WHERE UPPER(Name)='INSS' LIMIT 1");
	$rowInss = mysqli_fetch_assoc($resultInss);
	$inssId = $rowInss ? (int) $rowInss['RecId'] : 0;

	$resultSimples = mysqli_query($conn, "SELECT RecId FROM TaxTypeTable WHERE UPPER(Name)='SIMPLES NACIONAL' LIMIT 1");
	$rowSimples = mysqli_fetch_assoc($resultSimples);
	$simplesId = $rowSimples ? (int) $rowSimples['RecId'] : 0;

	if ($inssId <= 0 || $simplesId <= 0) {
		throw new Exception('Tax type IDs could not be resolved.');
	}

	// 2) Link existing tax journal lines to the right tax type.
	mysqli_query(
		$conn,
		"UPDATE LedgerJournalTrans t
		 INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
		 SET t.TaxTypeId = {$inssId},
			 t.ModifiedDateTime = NOW()
		 WHERE j.JournalType='TAX'
		   AND t.IsActive='1'
		   AND (t.TaxTypeId IS NULL OR t.TaxTypeId = 0)
		   AND UPPER(t.Description) LIKE '%INSS%'"
	);
	$inssUpdated = mysqli_affected_rows($conn);

	mysqli_query(
		$conn,
		"UPDATE LedgerJournalTrans t
		 INNER JOIN LedgerJournalTable j ON j.RecId = t.JournalRecId
		 SET t.TaxTypeId = {$simplesId},
			 t.ModifiedDateTime = NOW()
		 WHERE j.JournalType='TAX'
		   AND t.IsActive='1'
		   AND (t.TaxTypeId IS NULL OR t.TaxTypeId = 0)
		   AND UPPER(t.Description) LIKE '%SIMPLES NACIONAL%'"
	);
	$simplesUpdated = mysqli_affected_rows($conn);

	// 3) Ensure menu entries exist for tax types and bank accounts.
	mysqli_query(
		$conn,
		"INSERT IGNORE INTO SysMenuItem (GroupId, ParentMenuId, MenuCode, LabelKey, ViewKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
		 SELECT g.RecId,0,'GEN_TAX_TYPES','menu.general.taxtypes','tax-types',6,'1',NOW(),NOW(),'SYSTEM'
		 FROM SysMenuGroup g WHERE g.GroupCode='GENERAL'"
	);
	mysqli_query(
		$conn,
		"INSERT IGNORE INTO SysMenuItem (GroupId, ParentMenuId, MenuCode, LabelKey, ViewKey, SequenceNo, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
		 SELECT g.RecId,0,'GEN_BANK_ACCOUNTS','menu.general.bankaccounts','bank-accounts',7,'1',NOW(),NOW(),'SYSTEM'
		 FROM SysMenuGroup g WHERE g.GroupCode='GENERAL'"
	);

	// 4) Ensure labels for menus/modules.
	mysqli_query(
		$conn,
		"INSERT INTO SysLabelText (LabelKey, LanguageId, TextValue, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy)
		 VALUES
			('menu.general.taxtypes','EN-US','Tax Types','1',NOW(),NOW(),'SYSTEM'),
			('menu.general.taxtypes','PT-BR','Tipos de Imposto','1',NOW(),NOW(),'SYSTEM'),
			('menu.general.bankaccounts','EN-US','Bank Accounts','1',NOW(),NOW(),'SYSTEM'),
			('menu.general.bankaccounts','PT-BR','Contas Bancarias','1',NOW(),NOW(),'SYSTEM'),
			('module.taxtypes.title','EN-US','Tax Types','1',NOW(),NOW(),'SYSTEM'),
			('module.taxtypes.title','PT-BR','Tipos de Imposto','1',NOW(),NOW(),'SYSTEM'),
			('module.taxtypes.subtitle','EN-US','Maintain tax classification used by tax journals.','1',NOW(),NOW(),'SYSTEM'),
			('module.taxtypes.subtitle','PT-BR','Mantenha a classificacao de impostos usada nos diarios de imposto.','1',NOW(),NOW(),'SYSTEM'),
			('module.bankaccounts.title','EN-US','Bank Accounts','1',NOW(),NOW(),'SYSTEM'),
			('module.bankaccounts.title','PT-BR','Contas Bancarias','1',NOW(),NOW(),'SYSTEM'),
			('module.bankaccounts.subtitle','EN-US','Maintain company bank accounts used in journals.','1',NOW(),NOW(),'SYSTEM'),
			('module.bankaccounts.subtitle','PT-BR','Mantenha contas bancarias da empresa usadas nos diarios.','1',NOW(),NOW(),'SYSTEM')
		 ON DUPLICATE KEY UPDATE TextValue = VALUES(TextValue), ModifiedDateTime = NOW()"
	);

	mysqli_commit($conn);

	echo 'INSS_ID=' . $inssId . PHP_EOL;
	echo 'SIMPLES_ID=' . $simplesId . PHP_EOL;
	echo 'JOURNAL_LINES_INSS_UPDATED=' . $inssUpdated . PHP_EOL;
	echo 'JOURNAL_LINES_SIMPLES_UPDATED=' . $simplesUpdated . PHP_EOL;
	echo 'FIX_STATUS=OK' . PHP_EOL;
} catch (Exception $e) {
	mysqli_rollback($conn);
	fwrite(STDERR, 'FIX_STATUS=ERROR' . PHP_EOL);
	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);
}
