/**
 * Vitest Setup File
 * 
 * This file runs before all tests and sets up the testing environment.
 * It configures fast-check for property-based testing with appropriate defaults.
 */

import { beforeAll, afterEach, beforeEach } from 'vitest';
import { config } from '@vue/test-utils';

// Configure Vue Test Utils
beforeAll(() => {
  // Set global config for Vue Test Utils if needed
  config.global.stubs = {
    // Add any global stubs here if needed
    Teleport: true, // Stub Teleport to render in place for testing
  };
});

// Setup DOM for each test
beforeEach(() => {
  // Ensure document.body exists for Teleport target
  if (!document.body) {
    document.body = document.createElement('body');
  }
});

// Clean up after each test
afterEach(() => {
  // Clear any mocks or reset state if needed
});

// Configure fast-check defaults
// Property-based tests will run with minimum 100 iterations as per design spec
export const FC_CONFIG = {
  numRuns: 100, // Minimum iterations for property-based tests
  verbose: true,
};
