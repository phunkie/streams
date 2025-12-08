<?php

use function Phunkie\Effect\Functions\io\io;
use function Phunkie\Streams\Functions\file\exists;
use function Phunkie\Streams\Functions\file\readFileContents;
use function Phunkie\Streams\Functions\file\readLines;
use function Phunkie\Streams\Functions\file\writeFileContents;

use Phunkie\Streams\IO\File\Path;

describe("Error Handling with attempt() and handleError()", function () {

    describe("attempt() method", function () {

        it("returns a Validation Success for successful operations", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/error_test_' . uniqid() . '.txt');

            try {
                writeFileContents($tempFile, "test content")
                    ->unsafeRunSync();

                $result = readFileContents($tempFile)
                    ->attempt()
                    ->unsafeRunSync();

                // If successful, getOrElse returns the actual value
                $content = $result->getOrElse(null);
                expect($content)->toBe("test content");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("returns a Validation Failure for failed operations", function () {
            $nonExistentFile = new Path('/nonexistent/file_' . uniqid() . '.txt');

            $result = readFileContents($nonExistentFile)
                ->attempt()
                ->unsafeRunSync();

            // On failure, getOrElse returns the default
            $content = $result->getOrElse("fallback");
            expect($content)->toBe("fallback");
        });

        it("allows mapping over Validation results", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/map_test_' . uniqid() . '.txt');

            try {
                writeFileContents($tempFile, "hello")
                    ->unsafeRunSync();

                $result = readFileContents($tempFile)
                    ->attempt()
                    ->map(function ($validation) {
                        return $validation->getOrElse("error");
                    })
                    ->unsafeRunSync();

                expect($result)->toBe("hello");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("preserves error information in Validation", function () {
            $nonExistentFile = new Path('/nonexistent/validation_' . uniqid() . '.txt');

            $result = readFileContents($nonExistentFile)
                ->attempt()
                ->map(function ($validation) {
                    // Check if we got a default value (meaning failure)
                    $value = $validation->getOrElse("FAILED");

                    return $value === "FAILED" ? "error_detected" : $value;
                })
                ->unsafeRunSync();

            expect($result)->toBe("error_detected");
        });
    });

    describe("handleError() method", function () {

        it("recovers from errors with a fallback value", function () {
            $nonExistentFile = new Path('/nonexistent/recovery_' . uniqid() . '.txt');

            $result = readFileContents($nonExistentFile)
                ->handleError(function ($error) {
                    return "recovered successfully";
                })
                ->unsafeRunSync();

            expect($result)->toBe("recovered successfully");
        });

        it("provides access to the error in the handler", function () {
            $nonExistentFile = new Path('/nonexistent/error_access_' . uniqid() . '.txt');

            $result = readFileContents($nonExistentFile)
                ->handleError(function ($error) {
                    // Error should be a RuntimeException from failed fopen
                    return get_class($error);
                })
                ->unsafeRunSync();

            // Should return the exception class name
            expect($result)->toBe('RuntimeException');
        });

        it("allows chaining after error recovery", function () {
            $nonExistentFile = new Path('/nonexistent/chain_' . uniqid() . '.txt');

            $result = readFileContents($nonExistentFile)
                ->handleError(fn ($e) => "fallback")
                ->map(fn ($content) => strtoupper($content))
                ->unsafeRunSync();

            expect($result)->toBe("FALLBACK");
        });

        it("can perform IO operations in error handler", function () {
            $primaryFile = new Path('/nonexistent/primary_' . uniqid() . '.txt');
            $fallbackFile = new Path(sys_get_temp_dir() . '/fallback_' . uniqid() . '.txt');

            try {
                writeFileContents($fallbackFile, "fallback content")
                    ->unsafeRunSync();

                $result = readFileContents($primaryFile)
                    ->handleError(function ($error) use ($fallbackFile) {
                        return readFileContents($fallbackFile)
                            ->unsafeRunSync();
                    })
                    ->unsafeRunSync();

                expect($result)->toBe("fallback content");
            } finally {
                if (file_exists($fallbackFile->toString())) {
                    unlink($fallbackFile->toString());
                }
            }
        });

        it("does not call error handler on success", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/success_' . uniqid() . '.txt');
            $handlerCalled = false;

            try {
                writeFileContents($tempFile, "success")
                    ->unsafeRunSync();

                $result = readFileContents($tempFile)
                    ->handleError(function ($error) use (&$handlerCalled) {
                        $handlerCalled = true;

                        return "error";
                    })
                    ->unsafeRunSync();

                expect($result)->toBe("success");
                expect($handlerCalled)->toBeFalse();
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Combining attempt() and handleError()", function () {

        it("can use both for flexible error handling", function () {
            $nonExistentFile = new Path('/nonexistent/combined_' . uniqid() . '.txt');

            $result = readFileContents($nonExistentFile)
                ->handleError(fn ($e) => "handled")
                ->attempt()
                ->map(fn ($v) => $v->getOrElse("unexpected"))
                ->unsafeRunSync();

            expect($result)->toBe("handled");
        });

        it("attempt after handleError captures success", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/attempt_after_' . uniqid() . '.txt');

            try {
                writeFileContents($tempFile, "content")
                    ->unsafeRunSync();

                $result = readFileContents($tempFile)
                    ->handleError(fn ($e) => "error")
                    ->attempt()
                    ->map(fn ($v) => $v->getOrElse("fallback"))
                    ->unsafeRunSync();

                expect($result)->toBe("content");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Error handling in chains", function () {

        it("handles errors in flatMap chains", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/chain_error_' . uniqid() . '.txt');
            $nonExistentFile = new Path('/nonexistent/chain_target_' . uniqid() . '.txt');

            try {
                $result = writeFileContents($tempFile, "step1")
                    ->flatMap(fn ($_) => readFileContents($nonExistentFile))
                    ->handleError(fn ($e) => "recovered")
                    ->unsafeRunSync();

                expect($result)->toBe("recovered");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });

        it("propagates errors through chains", function () {
            $nonExistentFile = new Path('/nonexistent/propagate_' . uniqid() . '.txt');

            $result = readFileContents($nonExistentFile)
                ->map(fn ($content) => strtoupper($content))
                ->map(fn ($content) => "PREFIX: " . $content)
                ->attempt()
                ->map(fn ($v) => $v->getOrElse("failed"))
                ->unsafeRunSync();

            expect($result)->toBe("failed");
        });

        it("handles errors at any point in the chain", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/mid_chain_' . uniqid() . '.txt');

            try {
                writeFileContents($tempFile, "data")
                    ->unsafeRunSync();

                $result = readFileContents($tempFile)
                    ->map(function ($content) {
                        if ($content === "data") {
                            throw new \RuntimeException("Simulated error");
                        }

                        return $content;
                    })
                    ->handleError(fn ($e) => "caught: " . $e->getMessage())
                    ->unsafeRunSync();

                expect($result)->toBe("caught: Simulated error");
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });

    describe("Practical error handling patterns", function () {

        it("implements retry-like behavior with fallback", function () {
            $primary = new Path('/nonexistent/primary_' . uniqid() . '.txt');
            $secondary = new Path('/nonexistent/secondary_' . uniqid() . '.txt');
            $tertiary = new Path(sys_get_temp_dir() . '/tertiary_' . uniqid() . '.txt');

            try {
                writeFileContents($tertiary, "tertiary")
                    ->unsafeRunSync();

                $result = readFileContents($primary)
                    ->handleError(function ($e) use ($secondary) {
                        return readFileContents($secondary)
                            ->unsafeRunSync();
                    })
                    ->handleError(function ($e) use ($tertiary) {
                        return readFileContents($tertiary)
                            ->unsafeRunSync();
                    })
                    ->unsafeRunSync();

                expect($result)->toBe("tertiary");
            } finally {
                if (file_exists($tertiary->toString())) {
                    unlink($tertiary->toString());
                }
            }
        });

        it("validates file existence before operations", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/validate_' . uniqid() . '.txt');

            try {
                $result = exists($tempFile)
                    ->flatMap(function ($fileExists) use ($tempFile) {
                        if (!$fileExists) {
                            return writeFileContents($tempFile, "created")
                                ->map(fn ($_) => "created");
                        }

                        return io(fn () => "already exists");
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

        it("handles multiple error types differently", function () {
            $result = io(function () {
                throw new \InvalidArgumentException("Invalid!");
            })
                ->handleError(function ($error) {
                    if ($error instanceof \InvalidArgumentException) {
                        return "handled invalid argument";
                    }

                    return "handled other error";
                })
                ->unsafeRunSync();

            expect($result)->toBe("handled invalid argument");
        });

        it("composes error handlers for reusability", function () {
            $safeRead = function (Path $path) {
                return readFileContents($path)
                    ->handleError(fn ($e) => "");
            };

            $nonExistent = new Path('/nonexistent/compose_' . uniqid() . '.txt');
            $result = $safeRead($nonExistent);

            expect($result->unsafeRunSync())->toBe("");
        });
    });

    describe("Error handling with readLines", function () {

        it("handles errors when reading non-existent file lines", function () {
            $nonExistentFile = new Path('/nonexistent/lines_' . uniqid() . '.txt');

            $result = readLines($nonExistentFile)
                ->handleError(fn ($e) => [])
                ->unsafeRunSync();

            expect($result)->toBe([]);
        });

        it("successfully reads existing file lines", function () {
            $tempFile = new Path(sys_get_temp_dir() . '/lines_success_' . uniqid() . '.txt');

            try {
                file_put_contents($tempFile->toString(), "line1\nline2\nline3");

                $result = readLines($tempFile)
                    ->handleError(fn ($e) => [])
                    ->unsafeRunSync();

                expect($result)->toBe(["line1", "line2", "line3"]);
            } finally {
                if (file_exists($tempFile->toString())) {
                    unlink($tempFile->toString());
                }
            }
        });
    });
});
