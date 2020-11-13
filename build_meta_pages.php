<?php
ob_start();
?>
---
layout: archives
title: "%TITLE%"
permalink: %PERMALINK%
posts:
%POSTS%
---

<?php
$archiveTemplate = ob_get_clean();

chdir(dirname(__FILE__));

$termsFileContents = file_get_contents('_data/terms.json');
$terms = json_decode($termsFileContents, true);

// This block should produce a result only if the old archive links are still in the footer.html file
$footerFileContents = file_get_contents('_includes/footer.html');
preg_match_all("/<li><a href='([^']+)'>([^<]+)</", $footerFileContents, $regs, PREG_SET_ORDER);
if (!empty($regs)) {
    $results = array();
    foreach ($regs as $reg) {
        $results[] = (object) [
            'permalink' => rtrim(str_replace('http://www.mangaleera.com/index.php', '', $reg[1]), '/'),
            'name' => $reg[2],
        ];
    }
    $terms['archives'] = $results;

    $termsFileContents = json_encode($terms, JSON_PRETTY_PRINT);
    file_put_contents('_data/terms.json', $termsFileContents);
}

// ----------------------
//  POSTS
// ----------------------

// Sorting posts by date
$postsByMonth = [];
$files = scandir('_posts');
foreach ($files as $file) {
    if (preg_match('/\.md$/', $file)) {
        $fileContents = file_get_contents("_posts/{$file}");
        $startIndex = stripos($fileContents, '---') + 3;
        $endIndex = stripos($fileContents, '---', $startIndex);
        $frontMatter = substr($fileContents, $startIndex, $endIndex - $startIndex);
        
        $lines = explode("\n", trim($frontMatter));
        $illustration = NULL;
        $nextLineIsIllustration = false;
        foreach ($lines as $line) {
            if (preg_match('/^date: (.+)$/', $line, $regs)) {
                $date = $regs[1];
            }

            if (preg_match('/^title: "(.+)"$/', $line, $regs)) {
                $title = $regs[1];
            }

            if (preg_match('/^slug: (.+)$/', $line, $regs)) {
                $slug = $regs[1];
            }

            if ($nextLineIsIllustration) {
                $nextLineIsIllustration = false;
                preg_match('/^\s+value: "(.+)"$/', $line, $regs);
                $illustration = $regs[1];
            }
            
            if (strpos($line, 'name: ".illustration"') !== false) {
                $nextLineIsIllustration = true;
            }
        }

        $postContents = substr($fileContents, $endIndex + 3);
        $postContents = str_replace("\n", '', $postContents);
        $postContents = strip_tags($postContents);
        $postContents = cleanMarkdownTags($postContents);
        $postContents = trim($postContents);
        $words = explode(' ', $postContents);
        $excerpt = '';
        $limit = 350;
        foreach ($words as $word) {
            if (mb_strlen($excerpt) + mb_strlen($word) + 1 > $limit) {
                break;
            }
            else {
                $excerpt .= " {$word}";
            }
        }
        $excerpt = str_replace('"', '\"', $excerpt);
        $excerpt = trim($excerpt);

        $month = substr($date, 0, 7);
        if (!array_key_exists($month, $postsByMonth)) {
            $postsByMonth[$month] = [];
        }
        $postsByMonth[$month][] = [
            'date' => $date,
            'slug' => $slug,
            'title' => $title,
            'illustration' => $illustration,
            'excerpt' => $excerpt
        ];
    }
}

foreach ($postsByMonth as &$postsOfAMonth) {
    usort($postsOfAMonth, function($a, $b) {
        return -strcmp($a['date'], $b['date']);
    });
}

function cleanMarkdownTags($contents) {
    $result = preg_replace('/\*\*(.+)\*\*/U', '$1', $contents);
    $result = preg_replace('/_(.+)_/U', '$1', $result);
    $result = preg_replace('/!\[.+\]\(.+\)/U', '', $result);
    $result = preg_replace('/\[(.+)\]\(.+\)/U', '$1', $result);
    $result = preg_replace('/-{3,}/', '', $result);
    return $result;
}

// ----------------------
//  ARCHIVES
// ----------------------

// Removing existing archive files
$files = scandir('_archives');
foreach ($files as $file) {
    if (preg_match('/^archive-\d{4}-\d{2}\.html$/', $file)) {
        unlink("_archives/{$file}");
    }
}

// Building archive files
foreach ($terms['archives'] as $archive) {
    $title = "Archives de {$archive['name']}";
    $permalink = $archive['permalink'];
    $month = str_replace('/', '-', ltrim($permalink, '/'));
    $filename = sprintf("_archives/archive-%s.html", $month);

    $posts = '';
    foreach ($postsByMonth[$month] as $post) {
        $posts .= "  -\n";
        $posts .= "    title: \"{$post['title']}\"\n";
        $posts .= "    slug: {$post['slug']}\n";
        $posts .= "    excerpt: \"{$post['excerpt']}\"\n";
        if (!empty($post['illustration'])) {
            $posts .= "    illustration: \"{$post['illustration']}\"\n";
        }
    }
    $posts = rtrim($posts);

    $archiveContents = str_replace([ '%TITLE%', '%PERMALINK%', '%POSTS%' ], [ $title, $permalink, $posts ], $archiveTemplate);
    file_put_contents($filename, $archiveContents);
}
