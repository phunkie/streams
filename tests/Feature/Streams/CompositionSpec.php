<?php

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\IO\File\Path;
use function Phunkie\Streams\IO\File\exists;
use function Phunkie\Streams\IO\File\readFileContents;
use function Phunkie\Streams\IO\File\writeFileContents;
use function Phunkie\Streams\IO\File\readLines;
use function Phunkie\Streams\IO\File\writeLines;
use function Phunkie\Streams\IO\File\deleteFile;
use function Phunkie\Effect\Functions\io\io;

describe("Stream Composition with flatMap", function () {

    describe("Basic flatMap operations", function () {

        it("sequences dependent IO operations", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/flatmap_basic_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "test")
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->unsafeRunSync();

                expect($result)->toBe("test");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("chains multiple flatMaps", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/flatmap_chain_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "hello")
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->flatMap(fn($content) => writeFileContents($tempFile, strtoupper($content)))
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->unsafeRunSync();

                expect($result)->toBe("HELLO");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("passes values through the chain", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/flatmap_values_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "data")
                    ->flatMap(fn($bytesWritten) =>
                        readFileContents($tempFile)
                            ->map(fn($content) => ['bytes' => $bytesWritten, 'content' => $content])
                    )
                    ->unsafeRunSync();

                expect($result['bytes'])->toBe(4);
                expect($result['content'])->toBe("data");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("flatMap vs map", function () {

        it("map transforms values", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/map_test_' . uniqid() . '.txt');

            try {
                writeFileContents($tempFile, "test")->unsafeRunSync();

                $result = readFileContents($tempFile)
                    ->map(fn($content) => strtoupper($content))
                    ->unsafeRunSync();

                expect($result)->toBe("TEST");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("flatMap flattens nested IO", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/flatmap_nest_' . uniqid() . '.txt');

            try {
                writeFileContents($tempFile, "test")->unsafeRunSync();

                $result = readFileContents($tempFile)
                    ->flatMap(fn($content) => io(fn() => strtoupper($content)))
                    ->unsafeRunSync();

                expect($result)->toBe("TEST");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("combines map and flatMap effectively", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/combined_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "hello")
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->map(fn($content) => trim($content))
                    ->map(fn($content) => strtoupper($content))
                    ->flatMap(fn($upper) => writeFileContents($tempFile, $upper))
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->unsafeRunSync();

                expect($result)->toBe("HELLO");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Conditional composition", function () {

        it("branches based on condition", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/cond_' . uniqid() . '.txt');

            try {
                $result = exists($tempFile)
                    ->flatMap(function($fileExists) use ($tempFile) {
                        if ($fileExists) {
                            return readFileContents($tempFile);
                        } else {
                            return writeFileContents($tempFile, "new")
                                ->map(fn($_) => "created");
                        }
                    })
                    ->unsafeRunSync();

                expect($result)->toBe("created");
                expect(file_exists($tempFile->toString()))->toBeTrue();
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("validates before proceeding", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/validate_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "valid content")
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->flatMap(function($content) {
                        if (empty($content)) {
                            return io(fn() => throw new \RuntimeException("Empty content"));
                        }
                        return io(fn() => "validated: $content");
                    })
                    ->handleError(fn($e) => "error: " . $e->getMessage())
                    ->unsafeRunSync();

                expect($result)->toBe("validated: valid content");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Complex compositions", function () {

        it("builds data processing pipelines", function () {
            $inputFile = new Path(sys_get_temp_dir() . '/pipeline_in_' . uniqid() . '.txt');
            $outputFile = new Path(sys_get_temp_dir() . '/pipeline_out_' . uniqid() . '.txt');

            try {
                $result = writeLines($inputFile, ["line1", "line2", "line3"])
                    ->flatMap(fn($_) => readLines($inputFile))
                    ->map(fn($lines) => array_map('strtoupper', $lines))
                    ->flatMap(fn($processed) => writeLines($outputFile, $processed))
                    ->flatMap(fn($_) => readLines($outputFile))
                    ->unsafeRunSync();

                expect($result)->toBe(["LINE1", "LINE2", "LINE3"]);
            } finally {
                if (file_exists($inputFile->toString())) unlink($inputFile->toString());
                if (file_exists($outputFile->toString())) unlink($outputFile->toString());
            }
        });

        it("handles nested compositions", function () {
            $file1 = new Path(sys_get_temp_dir() . '/nested1_' . uniqid() . '.txt');
            $file2 = new Path(sys_get_temp_dir() . '/nested2_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($file1, "data1")
                    ->flatMap(fn($_) =>
                        writeFileContents($file2, "data2")
                            ->flatMap(fn($_) =>
                                io(fn() => [
                                    'file1' => readFileContents($file1)->unsafeRunSync(),
                                    'file2' => readFileContents($file2)->unsafeRunSync(),
                                ])
                            )
                    )
                    ->unsafeRunSync();

                expect($result['file1'])->toBe("data1");
                expect($result['file2'])->toBe("data2");
            } finally {
                if (file_exists($file1->toString())) unlink($file1->toString());
                if (file_exists($file2->toString())) unlink($file2->toString());
            }
        });

        it("composes with error handling", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/error_comp_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "short")
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->flatMap(function($content) {
                        if (strlen($content) < 10) {
                            return io(fn() => "Content too short");
                        }
                        return io(fn() => $content);
                    })
                    ->handleError(fn($e) => "error")
                    ->unsafeRunSync();

                expect($result)->toBe("Content too short");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Reusable compositions", function () {

        it("creates composable helper functions", function () {
            $readOrCreate = function(Path $path, string $default): IO {
                return exists($path)
                    ->flatMap(function($fileExists) use ($path, $default) {
                        if ($fileExists) {
                            return readFileContents($path);
                        }
                        return writeFileContents($path, $default)
                            ->map(fn($_) => $default);
                    });
            };

            $tempFile = new Path(sys_get_temp_dir() . '/reusable_' . uniqid() . '.txt');

            try {
                $result1 = $readOrCreate($tempFile, "default")->unsafeRunSync();
                expect($result1)->toBe("default");

                $result2 = $readOrCreate($tempFile, "default")->unsafeRunSync();
                expect($result2)->toBe("default");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("composes multiple helper functions", function () {
            $safeWrite = function(Path $path, string $content): IO {
                return writeFileContents($path, $content)
                    ->handleError(fn($e) => 0);
            };

            $safeRead = function(Path $path): IO {
                return readFileContents($path)
                    ->handleError(fn($e) => "");
            };

            $tempFile = new Path(sys_get_temp_dir() . '/composed_' . uniqid() . '.txt');

            try {
                $result = $safeWrite($tempFile, "test")
                    ->flatMap(fn($_) => $safeRead($tempFile))
                    ->unsafeRunSync();

                expect($result)->toBe("test");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Real-world scenarios", function () {

        it("implements backup and process workflow", function () {
            $sourceFile = new Path(sys_get_temp_dir() . '/source_' . uniqid() . '.txt');
            $backupFile = new Path(sys_get_temp_dir() . '/backup_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($sourceFile, "important")
                    ->flatMap(fn($_) => readFileContents($sourceFile))
                    ->flatMap(fn($content) => writeFileContents($backupFile, $content))
                    ->flatMap(fn($_) => io(fn() => [
                        'source_exists' => file_exists($sourceFile->toString()),
                        'backup_exists' => file_exists($backupFile->toString()),
                    ]))
                    ->unsafeRunSync();

                expect($result['source_exists'])->toBeTrue();
                expect($result['backup_exists'])->toBeTrue();
            } finally {
                if (file_exists($sourceFile->toString())) unlink($sourceFile->toString());
                if (file_exists($backupFile->toString())) unlink($backupFile->toString());
            }
        });

        it("implements update with validation", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/update_valid_' . uniqid() . '.txt');

            $updateFile = function(Path $path, callable $transform): IO {
                return readFileContents($path)
                    ->map($transform)
                    ->flatMap(fn($newContent) => writeFileContents($path, $newContent))
                    ->map(fn($_) => "updated");
            };

            try {
                writeFileContents($tempFile, "original")->unsafeRunSync();

                $result = $updateFile($tempFile, fn($c) => strtoupper($c))
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->unsafeRunSync();

                expect($result)->toBe("ORIGINAL");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("implements conditional create or update", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/create_update_' . uniqid() . '.txt');

            $createOrUpdate = function(Path $path, string $content): IO {
                return exists($path)
                    ->flatMap(function($fileExists) use ($path, $content) {
                        if ($fileExists) {
                            return readFileContents($path)
                                ->flatMap(fn($existing) =>
                                    writeFileContents($path, $existing . "\n" . $content)
                                )
                                ->map(fn($_) => "updated");
                        }
                        return writeFileContents($path, $content)
                            ->map(fn($_) => "created");
                    });
            };

            try {
                $result1 = $createOrUpdate($tempFile, "line1")
                    ->unsafeRunSync();
                expect($result1)->toBe("created");

                $result2 = $createOrUpdate($tempFile, "line2")
                    ->unsafeRunSync();
                expect($result2)->toBe("updated");

                $content = readFileContents($tempFile)->unsafeRunSync();
                expect($content)->toContain("line1");
                expect($content)->toContain("line2");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Sequential vs parallel understanding", function () {

        it("demonstrates sequential execution with flatMap", function () {
            $file1 = new Path(sys_get_temp_dir() . '/seq1_' . uniqid() . '.txt');
            $file2 = new Path(sys_get_temp_dir() . '/seq2_' . uniqid() . '.txt');

            try {
                // Second operation depends on first (sequential)
                $result = writeFileContents($file1, "first")
                    ->flatMap(fn($bytes1) =>
                        writeFileContents($file2, "second (after $bytes1 bytes)")
                    )
                    ->flatMap(fn($_) => readFileContents($file2))
                    ->unsafeRunSync();

                expect($result)->toContain("second (after 5 bytes)");
            } finally {
                if (file_exists($file1->toString())) unlink($file1->toString());
                if (file_exists($file2->toString())) unlink($file2->toString());
            }
        });

        it("ensures operations run in order", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/order_' . uniqid() . '.txt');
            $steps = [];

            try {
                writeFileContents($tempFile, "step1")
                    ->map(function($_) use (&$steps) {
                        $steps[] = "wrote";
                        return null;
                    })
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->map(function($content) use (&$steps) {
                        $steps[] = "read: $content";
                        return strtoupper($content);
                    })
                    ->flatMap(fn($upper) => writeFileContents($tempFile, $upper))
                    ->map(function($_) use (&$steps) {
                        $steps[] = "wrote again";
                        return null;
                    })
                    ->unsafeRunSync();

                expect($steps)->toBe(["wrote", "read: step1", "wrote again"]);
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });
});
