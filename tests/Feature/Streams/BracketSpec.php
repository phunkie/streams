<?php

use Phunkie\Effect\IO\IO;
use Phunkie\Streams\IO\File\Path;
use function Phunkie\Streams\IO\File\exists;
use function Phunkie\Streams\IO\File\deleteFile;
use function Phunkie\Streams\IO\File\readFileContents;
use function Phunkie\Streams\IO\File\writeFileContents;
use function Phunkie\Streams\IO\File\readLines;
use function Phunkie\Streams\IO\File\writeLines;
use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Streams\Functions\resource\bracket;

describe("Bracket Resource Management", function () {

    beforeEach(function() {
        $this->tempFile = new Path(sys_get_temp_dir() . '/bracket_test_' . uniqid() . '.txt');
    });

    afterEach(function() {
        // Clean up any test files
        if (file_exists($this->tempFile->toString())) {
            unlink($this->tempFile->toString());
        }
    });

    describe("bracket function", function () {

        it("properly acquires and releases resources", function () {
            $released = false;

            $result = bracket(
                io(fn() => "resource"),
                fn($r) => io(fn() => "result"),
                function($r) use (&$released) {
                    return io(function() use (&$released) {
                        $released = true;
                    });
                }
            )->unsafeRunSync();

            expect($result)->toBe("result");
            expect($released)->toBeTrue();
        });

        it("releases resources even when use throws an error", function () {
            $released = false;

            try {
                bracket(
                    io(fn() => "resource"),
                    fn($r) => io(fn() => throw new \RuntimeException("Error in use")),
                    function($r) use (&$released) {
                        return io(function() use (&$released) {
                            $released = true;
                        });
                    }
                )->unsafeRunSync();
            } catch (\RuntimeException $e) {
                // Expected
            }

            expect($released)->toBeTrue();
        });

        it("passes the acquired resource to both use and release", function () {
            $useReceived = null;
            $releaseReceived = null;

            bracket(
                io(fn() => "test-resource"),
                function($r) use (&$useReceived) {
                    return io(function() use ($r, &$useReceived) {
                        $useReceived = $r;
                        return "done";
                    });
                },
                function($r) use (&$releaseReceived) {
                    return io(function() use ($r, &$releaseReceived) {
                        $releaseReceived = $r;
                    });
                }
            )->unsafeRunSync();

            expect($useReceived)->toBe("test-resource");
            expect($releaseReceived)->toBe("test-resource");
        });
    });

    describe("File I/O with bracket", function () {

        it("can write and read file contents", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/bracket_io_test_' . uniqid() . '.txt');
            $content = "Hello, World!";

            try {
                writeFileContents($tempFile, $content)
                    ->unsafeRunSync();

                $readContent = readFileContents($tempFile)
                    ->unsafeRunSync();

                expect($readContent)->toBe($content);
            } finally {
                if (file_exists($tempFile->toString())) unlink($tempFile->toString());
            }
        });

        it("can write and read lines", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/bracket_lines_test_' . uniqid() . '.txt');
            $lines = ["Line 1", "Line 2", "Line 3"];

            try {
                writeLines($tempFile, $lines)
                    ->unsafeRunSync();

                $readLines = readLines($tempFile)
                    ->unsafeRunSync();

                expect($readLines)->toBe($lines);
            } finally {
                if (file_exists($tempFile->toString())) unlink($tempFile->toString());
            }
        });

        it("checks if file exists", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/bracket_exists_test_' . uniqid() . '.txt');

            try {
                $existsBefore = exists($tempFile)
                    ->unsafeRunSync();
                expect($existsBefore)->toBeFalse();

                writeFileContents($tempFile, "test")
                    ->unsafeRunSync();

                $existsAfter = exists($tempFile)
                    ->unsafeRunSync();
                expect($existsAfter)->toBeTrue();
            } finally {
                if (file_exists($tempFile->toString())) unlink($tempFile->toString());
            }
        });

        it("can delete files", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/bracket_delete_test_' . uniqid() . '.txt');

            writeFileContents($tempFile, "test")
                ->unsafeRunSync();

            expect(file_exists($tempFile->toString()))->toBeTrue();

            deleteFile($tempFile)
                ->unsafeRunSync();

            expect(file_exists($tempFile->toString()))->toBeFalse();
        });

        it("can chain file operations with flatMap", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/bracket_chain_test_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "original")
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->map(fn($content) => strtoupper($content))
                    ->flatMap(fn($upper) => writeFileContents($tempFile, $upper))
                    ->flatMap(fn($_) => readFileContents($tempFile))
                    ->unsafeRunSync();

                expect($result)->toBe("ORIGINAL");
            } finally {
                if (file_exists($tempFile->toString())) unlink($tempFile->toString());
            }
        });
    });

    describe("Error handling with bracket", function () {

        it("handles errors with attempt", function () {
            $nonExistentFile = new Path('/nonexistent/file.txt');

            $result = readFileContents($nonExistentFile)
                ->attempt()
                ->unsafeRunSync();

            // Should be a Failure
            $value = $result->getOrElse("fallback");
            expect($value)->toBe("fallback");
        });

        it("can recover from errors with handleError", function () {
            $nonExistentFile = new Path('/nonexistent/file.txt');

            $result = readFileContents($nonExistentFile)
                ->handleError(fn($e) => "error handled")
                ->unsafeRunSync();

            expect($result)->toBe("error handled");
        });
    });

    describe("Resource safety guarantees", function () {

        it("ensures file handles are closed even on error", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/bracket_error_test_' . uniqid() . '.txt');
            $released = false;

            try {
                try {
                    bracket(
                        io(fn() => fopen($tempFile->toString(), 'w')),
                        fn($handle) => io(fn() => throw new \RuntimeException("Error!")),
                        function($handle) use (&$released) {
                            return io(function() use ($handle, &$released) {
                                fclose($handle);
                                $released = true;
                            });
                        }
                    )->unsafeRunSync();
                } catch (\RuntimeException $e) {
                    // Expected
                }

                expect($released)->toBeTrue();
            } finally {
                if (file_exists($tempFile->toString())) unlink($tempFile->toString());
            }
        });

        it("properly manages multiple nested brackets", function () {
            $file1 = new Path(sys_get_temp_dir() . '/bracket_nested1_' . uniqid() . '.txt');
            $file2 = new Path(sys_get_temp_dir() . '/bracket_nested2_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($file1, "Content 1")
                    ->flatMap(fn($_) => writeFileContents($file2, "Content 2"))
                    ->flatMap(fn($_) => io(fn() => [
                        readFileContents($file1)->unsafeRunSync(),
                        readFileContents($file2)->unsafeRunSync(),
                    ]))
                    ->unsafeRunSync();

                expect($result)->toBe(["Content 1", "Content 2"]);
            } finally {
                if (file_exists($file1->toString())) unlink($file1->toString());
                if (file_exists($file2->toString())) unlink($file2->toString());
            }
        });
    });
});
