# Media Screen Testing Documentation

This directory contains tests for the Media Screen feature, following a dual testing strategy with both unit tests and property-based tests.

## Testing Strategy

### Unit Tests
Unit tests verify specific examples and edge cases:
- Located in `tests/unit/`
- Test specific behaviors with known inputs and outputs
- Cover edge cases like empty states, error conditions, and boundary values
- Use Vitest and Vue Test Utils

### Property-Based Tests
Property-based tests verify universal properties across all inputs:
- Located in `tests/property/`
- Test invariants that should hold for any valid input
- Use fast-check to generate random test data
- Run minimum 100 iterations per property (configured in `tests/setup.js`)

## Running Tests

```bash
# Run all tests
npm run test

# Run tests in watch mode
npm run test:watch

# Run only unit tests
npm run test:unit

# Run only property-based tests
npm run test:property

# Run tests with coverage report
npm run test:coverage

# Run tests with UI
npm run test:ui
```

## Test Configuration

### Vitest Configuration
- Configuration file: `vitest.config.js`
- Environment: happy-dom (lightweight DOM implementation)
- Setup file: `tests/setup.js`
- Path alias: `@/` maps to `resources/js/`

### Fast-Check Configuration
- Minimum iterations: 100 (defined in `tests/setup.js` as `FC_CONFIG`)
- Verbose mode enabled for detailed output
- All property tests should use `FC_CONFIG` for consistency

## TypeScript Support

TypeScript is configured for the entire project:
- Configuration file: `tsconfig.json`
- All type definitions in: `resources/js/types/media-screen.ts`
- Strict mode enabled for maximum type safety

## Writing Tests

### Unit Test Example
```typescript
import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import MyComponent from '@/Components/MyComponent.vue';

describe('MyComponent', () => {
  it('should render correctly', () => {
    const wrapper = mount(MyComponent, {
      props: { /* props */ }
    });
    expect(wrapper.text()).toContain('Expected text');
  });
});
```

### Property-Based Test Example
```typescript
import { describe, it, expect } from 'vitest';
import fc from 'fast-check';
import { FC_CONFIG } from '../setup';

// Feature: media-screen, Property N: [property description]
describe('Property: [Property Name]', () => {
  it('should maintain invariant across all inputs', () => {
    fc.assert(
      fc.property(
        fc.array(fc.integer()),
        (input) => {
          // Test the property
          expect(/* assertion */).toBe(/* expected */);
        }
      ),
      FC_CONFIG
    );
  });
});
```

## Coverage Goals

- Components: 90%+ line coverage
- Composables: 95%+ line coverage
- Validation functions: 100% line coverage

## Test Organization

```
tests/
├── setup.js              # Global test setup and configuration
├── README.md             # This file
├── unit/                 # Unit tests
│   └── types.test.ts     # Type definition tests
└── property/             # Property-based tests
    └── setup.test.ts     # Fast-check setup verification
```

## Requirements Validation

All tests should reference the requirements they validate:
- Use comments to link tests to requirements
- Format: `@Requirements: X.Y, X.Z`
- Property tests should also reference design properties
- Format: `// Feature: media-screen, Property N: [description]`

## Troubleshooting

### Tests not finding modules
- Check that path aliases are configured in both `vitest.config.js` and `tsconfig.json`
- Ensure imports use `@/` prefix for project files

### Fast-check tests failing
- Verify `FC_CONFIG` is imported from `tests/setup.js`
- Check that property assertions are correct for all possible inputs
- Use `fc.pre()` to add preconditions if needed

### TypeScript errors
- Run `npm run build` to check for type errors
- Ensure all types are properly imported from `@/types/media-screen`
- Check that `tsconfig.json` includes all necessary files
