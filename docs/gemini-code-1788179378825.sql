WITH RECURSIVE affiliate_tree AS (
    -- 1. Anker: Root-Knoten auswählen (Partner ohne übergeordneten Partner)
    SELECT 
        p.*,
        0 AS depth,
        CAST(LPAD(p.bx_affiliate_id, 10, '0') AS CHAR(500)) AS path
    FROM 
        bx_affiliate_partner p
    WHERE 
        p.bx_affiliate_parent_id IS NULL

    UNION ALL

    -- 2. Rekursion: Untergeordnete Partner (Downline/Tiers) anfügen
    SELECT 
        child.*,
        parent.depth + 1 AS depth,
        CONCAT(parent.path, '/', LPAD(child.bx_affiliate_id, 10, '0')) AS path
    FROM 
        bx_affiliate_partner child
    INNER JOIN 
        affiliate_tree parent ON child.bx_affiliate_parent_id = parent.bx_affiliate_id
)
SELECT 
    t.*,
    -- Visualisierung der Einrückung für den Namen
    CONCAT(REPEAT('  ', t.depth), t.bx_affiliate_firstname, ' ', t.bx_affiliate_lastname) AS partner_name_indented
FROM 
    affiliate_tree t
ORDER BY 
    t.path;