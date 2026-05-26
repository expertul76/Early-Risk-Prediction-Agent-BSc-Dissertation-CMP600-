import { createContext, useContext, useReducer, type ReactNode } from 'react';
import type { AppState, AppAction } from './types';

const initialState: AppState = {
  step: 'reports',
  rawData: [],
  detectedSchema: null,
  students: [],
  settings: {
    openaiApiKey: sessionStorage.getItem('marcel_openai_key') || '',
    sentimentEnabled: false,
  },
  isProcessing: false,
  error: null,
  currentReportId: null,
  currentReportName: null,
  isSaved: false,
};

function appReducer(state: AppState, action: AppAction): AppState {
  switch (action.type) {
    case 'SET_STEP':
      return { ...state, step: action.payload, error: null };
    case 'SET_RAW_DATA':
      return { ...state, rawData: action.payload.data, detectedSchema: action.payload.schema };
    case 'SET_STUDENTS':
      return { ...state, students: action.payload };
    case 'UPDATE_STUDENT':
      return {
        ...state,
        students: state.students.map(s =>
          s.id === action.payload.id ? { ...s, ...action.payload.updates } : s
        ),
      };
    case 'SET_SETTINGS': {
      const newSettings = { ...state.settings, ...action.payload };
      if (action.payload.openaiApiKey !== undefined) {
        if (action.payload.openaiApiKey) {
          sessionStorage.setItem('marcel_openai_key', action.payload.openaiApiKey);
        } else {
          sessionStorage.removeItem('marcel_openai_key');
        }
      }
      return { ...state, settings: newSettings };
    }
    case 'SET_PROCESSING':
      return { ...state, isProcessing: action.payload };
    case 'SET_ERROR':
      return { ...state, error: action.payload, isProcessing: false };
    case 'LOAD_REPORT':
      return {
        ...state,
        step: 'dashboard',
        students: action.payload.students,
        currentReportId: action.payload.id,
        currentReportName: action.payload.name,
        isSaved: true,
        error: null,
      };
    case 'SET_SAVED':
      return {
        ...state,
        currentReportId: action.payload.id,
        currentReportName: action.payload.name,
        isSaved: true,
      };
    case 'RESET':
      return { ...initialState, settings: state.settings };
    default:
      return state;
  }
}

interface AppContextType {
  state: AppState;
  dispatch: React.Dispatch<AppAction>;
}

const AppContext = createContext<AppContextType | null>(null);

export function AppProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(appReducer, initialState);
  return (
    <AppContext.Provider value={{ state, dispatch }}>
      {children}
    </AppContext.Provider>
  );
}

export function useApp() {
  const context = useContext(AppContext);
  if (!context) throw new Error('useApp must be used within AppProvider');
  return context;
}
