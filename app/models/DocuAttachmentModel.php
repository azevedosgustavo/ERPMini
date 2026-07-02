<?php

class DocuAttachmentModel extends BaseModel
{
    public function allByEntity($entityName, $recordRecId, $lineEntityName = '', $lineRecId = 0)
    {
        return $this->db->fetchAll(
            'SELECT RecId, EntityName, RecordRecId, LineEntityName, LineRecId, FileName, MimeType, Notes, CreatedDateTime, CreatedBy
             FROM DocuAttachment
             WHERE EntityName = ? AND RecordRecId = ? AND LineEntityName = ? AND LineRecId = ? AND IsActive = "1"
             ORDER BY RecId DESC',
            [trim($entityName), (int) $recordRecId, trim($lineEntityName), (int) $lineRecId]
        );
    }

    public function create($data, $createdBy)
    {
        $entityName = trim($data['EntityName']);
        $recordRecId = (int) $data['RecordRecId'];
        $lineEntityName = isset($data['LineEntityName']) ? trim($data['LineEntityName']) : '';
        $lineRecId = isset($data['LineRecId']) ? (int) $data['LineRecId'] : 0;
        $fileName = trim($data['FileName']);
        $mimeType = isset($data['MimeType']) ? trim($data['MimeType']) : 'application/octet-stream';
        $fileContent = trim($data['FileContentBase64']);

        if ($entityName === '' || $recordRecId <= 0 || $fileName === '' || $fileContent === '') {
            throw new Exception('Attachment fields are required.');
        }

        $now = $this->now();

        return $this->db->insert(
            'INSERT INTO DocuAttachment (EntityName, RecordRecId, LineEntityName, LineRecId, FileName, MimeType, FileContentBase64, Notes, IsActive, CreatedDateTime, ModifiedDateTime, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "1", ?, ?, ?)',
            [
                $entityName,
                $recordRecId,
                $lineEntityName,
                $lineRecId,
                $fileName,
                $mimeType,
                $fileContent,
                isset($data['Notes']) ? trim($data['Notes']) : '',
                $now,
                $now,
                $createdBy
            ]
        );
    }

    public function findById($id)
    {
        return $this->db->fetchOne(
            'SELECT * FROM DocuAttachment WHERE RecId = ? AND IsActive = "1" LIMIT 1',
            [(int) $id]
        );
    }

    public function delete($id)
    {
        return $this->db->execute(
            'UPDATE DocuAttachment SET IsActive = "0", ModifiedDateTime = ? WHERE RecId = ?',
            [$this->now(), (int) $id]
        );
    }
}
