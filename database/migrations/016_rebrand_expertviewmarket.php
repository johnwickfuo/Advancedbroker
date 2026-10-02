<?php
declare(strict_types=1);

use App\Support\Database;

// Update only matching legacy branding. Preserve independently customised names.
return static function (Database $db): void {
    $db->execute(
        "UPDATE country_branding
         SET brand_name=CASE
               WHEN LOWER(TRIM(COALESCE(brand_name,''))) IN ('apextrades','apex trades')
               THEN 'ExpertViewMarket' ELSE brand_name END,
             short_name=CASE
               WHEN LOWER(TRIM(COALESCE(short_name,''))) IN ('apextrades','apex trades')
               THEN 'EXPERTVIEWMARKET' ELSE short_name END,
             meta_title=REPLACE(meta_title,'ApexTrades','ExpertViewMarket'),
             meta_description=REPLACE(meta_description,'ApexTrades','ExpertViewMarket'),
             contact_text=REPLACE(contact_text,'ApexTrades','ExpertViewMarket'),
             footer_text=REPLACE(footer_text,'ApexTrades','ExpertViewMarket'),
             updated_at=NOW()
         WHERE LOWER(TRIM(COALESCE(brand_name,''))) IN ('apextrades','apex trades')
            OR LOWER(TRIM(COALESCE(short_name,''))) IN ('apextrades','apex trades')
            OR COALESCE(meta_title,'') LIKE '%ApexTrades%'
            OR COALESCE(meta_description,'') LIKE '%ApexTrades%'
            OR COALESCE(contact_text,'') LIKE '%ApexTrades%'
            OR COALESCE(footer_text,'') LIKE '%ApexTrades%'"
    );

    // Rename existing branded FAQ copy without changing unrelated questions.
    $db->execute(
        "UPDATE faqs
         SET question=REPLACE(question,'ApexTrades','ExpertViewMarket'),
             answer=REPLACE(answer,'ApexTrades','ExpertViewMarket'),
             updated_at=NOW()
         WHERE question LIKE '%ApexTrades%' OR answer LIKE '%ApexTrades%'"
    );

    // Publish new branded revisions of the original platform legal templates,
    // retaining the older published versions for history. Do not supersede
    // a newer legal document that an administrator has already published.
    $db->execute(
        "INSERT INTO legal_documents
           (country_id,language_id,document_type,title,body,version,
            is_published,published_at,effective_at,created_by_user_id,created_at,updated_at)
         SELECT d.country_id,d.language_id,d.document_type,
                REPLACE(d.title,'ApexTrades','ExpertViewMarket'),
                REPLACE(d.body,'ApexTrades','ExpertViewMarket'),
                'expertviewmarket-2026-10-02',1,NOW(),NOW(),NULL,NOW(),NOW()
         FROM legal_documents d
         WHERE d.version='apextrades-2026-09-25'
           AND d.is_published=1
           AND NOT EXISTS (
               SELECT 1 FROM legal_documents newer
               WHERE newer.country_id=d.country_id
                 AND newer.language_id=d.language_id
                 AND newer.document_type=d.document_type
                 AND newer.is_published=1
                 AND (newer.effective_at IS NULL OR newer.effective_at<=NOW())
                 AND (COALESCE(newer.published_at,newer.created_at)>
                      COALESCE(d.published_at,d.created_at)
                     OR (COALESCE(newer.published_at,newer.created_at)=
                         COALESCE(d.published_at,d.created_at) AND newer.id>d.id))
           )
           AND NOT EXISTS (
               SELECT 1 FROM legal_documents existing
               WHERE existing.country_id=d.country_id
                 AND existing.language_id=d.language_id
                 AND existing.document_type=d.document_type
                 AND existing.version='expertviewmarket-2026-10-02'
           )"
    );
};
